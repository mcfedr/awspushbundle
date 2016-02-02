<?php

namespace Mcfedr\AwsPushBundle\Controller;

use Mcfedr\AwsPushBundle\Exception\PlatformNotConfiguredException;
use Mcfedr\AwsPushBundle\Form\Type\BroadcastType;
use Mcfedr\AwsPushBundle\Form\Type\DeviceType;
use Mcfedr\AwsPushBundle\Form\Model\Broadcast;
use Mcfedr\AwsPushBundle\Form\Model\Device;
use Mcfedr\JsonFormBundle\Controller\JsonController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $form = $this->createForm(DeviceType::class, $device);
        $this->handleJsonForm($form, $request);

        try {
            if (($arn = $this->get('mcfedr_aws_push.devices')->registerDevice($device->getDeviceId(), $device->getPlatform()))) {
                $this->has('logger') && $this->get('logger')->info('Device registered',  [
                    'arn' => $arn,
                    'device' => $device->getDeviceId(),
                    'platform' => $device->getPlatform()
                ]);

                if ($this->container->getParameter('mcfedr_aws_push.topic_arn')) {
                    $this->get('mcfedr_aws_push.sns_client')->subscribe([
                        'TopicArn' => $this->container->getParameter('mcfedr_aws_push.topic_arn'),
                        'Protocol' => 'application',
                        'Endpoint' => $arn
                    ]);
                }

                return new Response('Device registered', 200);
            }
        } catch (PlatformNotConfiguredException $e) {
            $this->has('logger') && $this->get('logger')->error('Unknown platform', [
                'e' => $e,
                'platform' => $device->getPlatform()
            ]);
            return new Response('Unknown platform', 400);
        } catch (\Exception $e) {
            $this->has('logger') && $this->get('logger')->error('Exception registering device', [
               'e' => $e,
                'device' => $device->getDeviceId(),
                'platform' => $device->getPlatform()
            ]);
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
        $form = $this->createForm(BroadcastType::class, $broadcast);
        $this->handleJsonForm($form, $request);

        try {
            if ($this->container->getParameter('mcfedr_aws_push.topic_arn') && !$broadcast->getPlatform()) {
                $this->get('mcfedr_aws_push.messages')->send($broadcast->getMessage(), $this->container->getParameter('mcfedr_aws_push.topic_arn'));
            } else {
                $this->get('mcfedr_aws_push.messages')->broadcast($broadcast->getMessage(), $broadcast->getPlatform());
            }

            return new Response('Message sent', 200);
        } catch (PlatformNotConfiguredException $e) {
            $this->has('logger') && $this->get('logger')->error('Unknown platform', [
                'e' => $e
            ]);
            return new Response('Unknown platform', 400);
        }
    }
}
