<?php

namespace mcfedr\AWSPushBundle\Controller;

use Aws\Sns\Exception\SubscriptionLimitExceededException;
use Aws\Sns\Exception\TopicLimitExceededException;
use mcfedr\AWSPushBundle\Exception\PlatformNotConfiguredException;
use mcfedr\AWSPushBundle\Message\Message;
use mcfedr\AWSPushBundle\Service\Devices;
use mcfedr\AWSPushBundle\Service\Messages;
use mcfedr\AWSPushBundle\Service\Topics;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * This should server as an example of how to use the services
 * provided by this bundle.
 * In some simple cases it may be enough to use this controller.
 *
 * @package mcfedr\AWSPushBundle\Controller
 * @Route(service="mcfedr_aws_push.api")
 */
class APIController extends Controller
{
    /**
     * @var Devices
     */
    private $devices;

    /**
     * @var Messages
     */
    private $messages;

    /**
     * @var Topics
     */
    private $topics;

    /**
     * @var string
     */
    private $topicName;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    /**
     * @param Devices $devices
     * @param Messages $messages
     * @param Topics $topics
     * @param string $topicName
     * @param LoggerInterface $logger
     * @param SecurityContextInterface $securityContext
     */
    function __construct(
        Devices $devices,
        Messages $messages,
        Topics $topics,
        $topicName,
        LoggerInterface $logger,
        SecurityContextInterface $securityContext
    ) {
        $this->devices = $devices;
        $this->messages = $messages;
        $this->topics = $topics;
        $this->topicName = $topicName;
        $this->logger = $logger;
        $this->securityContext = $securityContext;
    }

    /**
     * @Route("/devices")
     * @Method({"POST"})
     */
    public function registerDeviceAction(Request $request)
    {
        $data = $this->handleJSONRequest($request);
        if ($data instanceof Response) {
            return $data;
        }

        if (!isset($data['deviceID']) || !isset($data['platform'])) {
            $this->logger->error(
                'Missing parameters',
                [
                    'data' => $data
                ]
            );
            return new Response('Missing parameters', 400);
        }

        try {
            if (($arn = $this->devices->registerDevice($data['deviceID'], $data['platform']))) {
                $this->logger->info(
                    'Device registered',
                    [
                        'arn' => $arn,
                        'device' => $data['deviceID'],
                        'platform' => $data['platform']
                    ]
                );

                if ($this->topicName) {
                    try {
                        $this->topics->registerDeviceOnTopic($arn, $this->topicName);
                    } catch (SubscriptionLimitExceededException $e) {
                        $this->logger->error(
                            'Failed to subscription device to topic',
                            [
                                'deviceArn' => $arn,
                                'topicName' => $this->topicName,
                                'exception' => $e
                            ]
                        );
                        return new Response('Failed to subscribe device to topic', 500);
                    } catch (TopicLimitExceededException $e) {
                        $this->logger->error(
                            'Failed to create topic for device',
                            [
                                'deviceArn' => $arn,
                                'topicName' => $this->topicName,
                                'exception' => $e
                            ]
                        );
                        return new Response('Failed to create topic for device', 500);
                    }
                }

                return new Response('Device registered', 200);
            }
        } catch (PlatformNotConfiguredException $e) {
            $this->logger->error(
                'Unknown platform',
                [
                    'e' => $e,
                    'platform' => $data['platform']
                ]
            );
            return new Response('Unknown platform', 400);
        } catch (\Exception $e) {
            $this->logger->error(
                'Exception registering device',
                [
                    'e' => $e,
                    'device' => $data['deviceID'],
                    'platform' => $data['platform']
                ]
            );
        }

        return new Response('Unknown error', 500);
    }

    /**
     * @Route("/broadcast")
     * @Method({"POST"})
     */
    public function broadcastAction(Request $request)
    {
        if (!$this->securityContext->isGranted('ROLE_MCFEDR_AWS_BROADCAST')) {
            throw new AccessDeniedException();
        }

        $data = $this->handleJSONRequest($request);
        if ($data instanceof Response) {
            return $data;
        }

        if (!isset($data['message'])) {
            return new Response('You must send a message', 400);
        }

        try {
            $message = new Message($data['message']);
            $platform = isset($data['platform']) ? $data['platform'] : null;

            if ($this->topicName && !$platform) {
                $this->topics->broadcast($message, $this->topicName);
            } else {
                $this->messages->broadcast($message, $platform);
            }

            return new Response('Message sent', 200);
        } catch (PlatformNotConfiguredException $e) {
            return new Response('Unknown platform', 400);
        }
    }

    /**
     * Try to parse a json request
     *
     * @param Request $request
     * @return mixed|Response
     */
    private function handleJSONRequest(Request $request)
    {
        $content = $request->getContent();
        $data = json_decode($content, true);
        if ($data === null) {
            $this->logger->error(
                'Invalid JSON',
                [
                    'content' => $content
                ]
            );
            return new Response("Invalid Request JSON", 400);
        }
        return $data;
    }
}
