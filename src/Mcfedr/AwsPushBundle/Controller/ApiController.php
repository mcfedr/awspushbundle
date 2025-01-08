<?php

declare(strict_types=1);

namespace Mcfedr\AwsPushBundle\Controller;

use Aws\Sns\SnsClient;
use Mcfedr\AwsPushBundle\Exception\PlatformNotConfiguredException;
use Mcfedr\AwsPushBundle\Model\BroadcastRequest;
use Mcfedr\AwsPushBundle\Model\DeviceRequest;
use Mcfedr\AwsPushBundle\Service\Devices;
use Mcfedr\AwsPushBundle\Service\Messages;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * This should server as an example of how to use the services
 * provided by this bundle.
 * In some simple cases it may be enough to use this controller.
 */
class ApiController extends AbstractController
{
    private Devices $devices;

    private Messages $messages;

    private SnsClient $snsClient;

    private ?string $topicArn;

    private SerializerInterface $serializer;

    private ValidatorInterface $validator;

    private ?LoggerInterface $logger;

    public function __construct(Devices $devices, Messages $messages, SnsClient $snsClient, ?string $topicArn, SerializerInterface $serializer, ValidatorInterface $validator, ?LoggerInterface $logger)
    {
        $this->devices = $devices;
        $this->messages = $messages;
        $this->snsClient = $snsClient;
        $this->topicArn = $topicArn;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    #[Route(path: '/devices', name: 'mcfedr_aws_push.register', methods: ['POST'])]
    public function registerDeviceAction(Request $request): Response
    {
        /** @var DeviceRequest $deviceRequest */
        $deviceRequest = $this->serializer->deserialize($request->getContent(), DeviceRequest::class, 'json');
        $errors = $this->validator->validate($deviceRequest);
        if (\count($errors)) {
            throw new BadRequestHttpException('Invalid register');
        }
        $device = $deviceRequest->getDevice();

        try {
            if ($arn = $this->devices->registerDevice($device->getDeviceId(), $device->getPlatform())) {
                $this->logger?->info('Device registered', [
                    'arn' => $arn,
                    'device' => $device->getDeviceId(),
                    'platform' => $device->getPlatform(),
                ]);

                if ($this->topicArn) {
                    $this->snsClient->subscribe([
                        'TopicArn' => $this->topicArn,
                        'Protocol' => 'application',
                        'Endpoint' => $arn,
                    ]);
                }

                return new Response('Device registered', 200);
            }
        } catch (PlatformNotConfiguredException $e) {
            $this->logger?->error('Unknown platform', [
                'e' => $e,
                'platform' => $device->getPlatform(),
            ]);

            return new Response('Unknown platform', 400);
        } catch (\Exception $e) {
            $this->logger?->error('Exception registering device', [
                'e' => $e,
                'device' => $device->getDeviceId(),
                'platform' => $device->getPlatform(),
            ]);
        }

        return new Response('Unknown error', 500);
    }

    #[Route(path: '/broadcast', name: 'mcfedr_aws_push.broadcast', methods: ['POST'])]
    public function broadcastAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MCFEDR_AWS_BROADCAST');

        /** @var BroadcastRequest $broadcastRequest */
        $broadcastRequest = $this->serializer->deserialize($request->getContent(), BroadcastRequest::class, 'json');
        $errors = $this->validator->validate($broadcastRequest);
        if (\count($errors)) {
            throw new BadRequestHttpException('Invalid broadcast');
        }
        $broadcast = $broadcastRequest->getBroadcast();

        try {
            if ($this->topicArn && !$broadcast->getPlatform()) {
                $this->messages->send($broadcast->getMessage(), $this->topicArn);
            } else {
                $this->messages->broadcast($broadcast->getMessage(), $broadcast->getPlatform());
            }

            return new Response('Message sent', 200);
        } catch (PlatformNotConfiguredException $e) {
            $this->logger?->error('Unknown platform', [
                'e' => $e,
            ]);

            return new Response('Unknown platform', 400);
        }
    }
}
