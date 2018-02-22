<?php

namespace Mcfedr\AwsPushBundle\Controller;

use Mcfedr\AwsPushBundle\Exception\PlatformNotConfiguredException;
use Mcfedr\AwsPushBundle\Model\Broadcast;
use Mcfedr\AwsPushBundle\Model\BroadcastRequest;
use Mcfedr\AwsPushBundle\Model\Device;
use Mcfedr\AwsPushBundle\Model\DeviceRequest;
use Mcfedr\AwsPushBundle\Service\Devices;
use Mcfedr\AwsPushBundle\Service\Messages;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * This should server as an example of how to use the services
 * provided by this bundle.
 * In some simple cases it may be enough to use this controller.
 */
class ApiController extends Controller
{
    /**
     * @Route("/devices", name="mcfedr_aws_push.register")
     * @Method({"POST"})
     */
    public function registerDeviceAction(Request $request, SerializerInterface $serializer, Devices $devices, ValidatorInterface $validator)
    {
        /** @var DeviceRequest $deviceRequest */
        $deviceRequest = $serializer->deserialize($request->getContent(), DeviceRequest::class, 'json');
        $errors = $validator->validate($deviceRequest);
        if (count($errors)) {
            throw new BadRequestHttpException('Invalid register');
        }
        $device = $deviceRequest->getDevice();

        try {
            if (($arn = $devices->registerDevice($device->getDeviceId(), $device->getPlatform()))) {
                $this->has('logger') && $this->get('logger')->info('Device registered', [
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
    public function broadcastAction(Request $request, SerializerInterface $serializer, Messages $messages, ValidatorInterface $validator)
    {
        /** @var BroadcastRequest $broadcastRequest */
        $broadcastRequest = $serializer->deserialize($request->getContent(), BroadcastRequest::class, 'json');
        $errors = $validator->validate($broadcastRequest);
        if (count($errors)) {
            throw new BadRequestHttpException('Invalid broadcast');
        }
        $broadcast = $broadcastRequest->getBroadcast();

        try {
            if ($this->container->getParameter('mcfedr_aws_push.topic_arn') && !$broadcast->getPlatform()) {
                $messages->send($broadcast->getMessage(), $this->container->getParameter('mcfedr_aws_push.topic_arn'));
            } else {
                $messages->broadcast($broadcast->getMessage(), $broadcast->getPlatform());
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
