<?php

namespace Mcfedr\AwsPushBundle\Controller;

use Aws\Sns\Exception\TopicLimitExceededException;
use Mcfedr\AwsPushBundle\Exception\PlatformNotConfiguredException;
use Mcfedr\AwsPushBundle\Form\BroadcastType;
use Mcfedr\AwsPushBundle\Form\DeviceType;
use Mcfedr\AwsPushBundle\Form\Model\Broadcast;
use Mcfedr\AwsPushBundle\Form\Model\Device;
use Mcfedr\AwsPushBundle\Service\Devices;
use Mcfedr\AwsPushBundle\Service\Messages;
use Mcfedr\AwsPushBundle\Service\Topics;
use Mcfedr\JsonForm\Controller\JsonController;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * This should server as an example of how to use the services
 * provided by this bundle.
 * In some simple cases it may be enough to use this controller.
 *
 * @package Mcfedr\AwsPushBundle\Controller
 */
class ApiController extends JsonController
{
    /**
     * @Route("/devices", name="mcfedr_aws_push.register")
     * @Method({"POST"})
     */
    public function registerDeviceAction(Request $request)
    {
        $device = new Device();
        $form = $this->createForm(new DeviceType(), $device);
        $this->handleJsonForm($form, $request);

        try {
            if (($arn = $this->get('mcfedr_aws_push.devices')->registerDevice($device->getDeviceId(), $device->getPlatform()))) {
                $this->has('logger') && $this->get('logger')->info(
                    'Device registered',
                    [
                        'arn' => $arn,
                        'device' => $device->getDeviceId(),
                        'platform' => $device->getPlatform()
                    ]
                );

                if ($this->container->getParameter('mcfedr_aws_push.topic_arn')) {
                    try {
                        $this->get('mcfedr_aws_push.topics')->registerDeviceOnTopic($arn, $this->container->getParameter('mcfedr_aws_push.topic_arn'));
                    } catch (TopicLimitExceededException $e) {
                        $this->has('logger') && $this->get('logger')->error(
                            'Failed to create topic for device',
                            [
                                'deviceArn' => $arn,
                                'topicArn' => $this->container->getParameter('mcfedr_aws_push.topic_arn'),
                                'exception' => $e
                            ]
                        );
                        return new Response('Failed to create topic for device', 500);
                    }
                }

                return new Response('Device registered', 200);
            }
        } catch (PlatformNotConfiguredException $e) {
            $this->has('logger') && $this->get('logger')->error(
                'Unknown platform',
                [
                    'e' => $e,
                    'platform' => $device->getPlatform()
                ]
            );
            return new Response('Unknown platform', 400);
        } catch (\Exception $e) {
            $this->has('logger') && $this->get('logger')->error(
                'Exception registering device',
                [
                    'e' => $e,
                    'device' => $device->getDeviceId(),
                    'platform' => $device->getPlatform()
                ]
            );
        }

        return new Response('Unknown error', 500);
    }

    /**
     * @Route("/broadcast", name="mcfedr_aws_push.broadcast")
     * @Method({"POST"})
     * @Security("has_role('ROLE_MCFEDR_AWS_BROADCAST')")
     */
    public function broadcastAction(Request $request)
    {
        $broadcast = new Broadcast();
        $form = $this->createForm(new BroadcastType(), $broadcast);
        $this->handleJsonForm($form, $request);

        try {
            if ($this->container->getParameter('mcfedr_aws_push.topic_arn') && !$broadcast->getPlatform()) {
                $this->get('mcfedr_aws_push.topics')->broadcast($broadcast->getMessage(), $this->container->getParameter('mcfedr_aws_push.topic_arn'));
            } else {
                $this->get('mcfedr_aws_push.messages')->broadcast($broadcast->getMessage(), $broadcast->getPlatform());
            }

            return new Response('Message sent', 200);
        } catch (PlatformNotConfiguredException $e) {
            return new Response('Unknown platform', 400);
        }
    }
}
