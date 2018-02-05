<?php

namespace Mcfedr\AwsPushBundle\Normalizer;

use Mcfedr\AwsPushBundle\Model\Device;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class DeviceNormalizer implements DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!isset($context[AbstractNormalizer::OBJECT_TO_POPULATE])) {
            throw new LogicException('Cannot denormalize data because context has not a ' . AbstractNormalizer::OBJECT_TO_POPULATE);
        }
        if (!$this->serializer instanceof DenormalizerInterface) {
            throw new LogicException('Cannot denormalize attributes because injected serializer is not a denormalizer');
        }

        /** @var Device $device */
        $device = &$context[AbstractNormalizer::OBJECT_TO_POPULATE];

        foreach ($data['device'] as $field => $val) {
            switch ($field) {
                case 'platform':
                    $device->setPlatform($val);
                    break;
                case 'deviceId':
                    $device->setDeviceId($val);
                    break;
            }
        }

        return $device;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return Device::class === $type;
    }
}
