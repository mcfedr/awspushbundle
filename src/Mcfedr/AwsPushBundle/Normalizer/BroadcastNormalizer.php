<?php

namespace Mcfedr\AwsPushBundle\Normalizer;

use Mcfedr\AwsPushBundle\Model\Broadcast;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class BroadcastNormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!isset($context[AbstractNormalizer::OBJECT_TO_POPULATE])) {
            throw new LogicException('Cannot denormalize data because context has not a ' . AbstractNormalizer::OBJECT_TO_POPULATE);
        }

        /** @var Broadcast $broadcast */
        $broadcast = &$context[AbstractNormalizer::OBJECT_TO_POPULATE];

        foreach ($data['broadcast'] as $field => $val) {
            switch ($field) {
                case 'platform':
                    $broadcast->setPlatform($val);
                    break;
                case 'message':
                    $broadcast->setMessage($val);
                    break;
            }
        }

        return $broadcast;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return Broadcast::class === $type;
    }
}
