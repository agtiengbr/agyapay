<?php
namespace AGTI\Yapay\Infrastructure\Serializer;

use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;

class Serializer
{
    public static function buildSerializer()
    {
        $encoders = [new JsonEncoder()];
        
        if (version_compare(_PS_VERSION_, '9', '<')) {
            $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter(), null, new ReflectionExtractor());
            $normalizer->setCircularReferenceHandler(function () {
                return -1;
            });
        } else {
            $normalizer = new class(null, new CamelCaseToSnakeCaseNameConverter(), null, new ReflectionExtractor()) extends ObjectNormalizer {
                public function normalize($object, $format = null, array $context = []) {
                    $context[AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER] = function ($object) {
                        return -1;
                    };
                    return parent::normalize($object, $format, $context);
                }
            };
        }

        $normalizers = [
            new DateTimeNormalizer(),
            $normalizer
        ];
        $serializer = new SymfonySerializer($normalizers, $encoders);

        return $serializer;
    }
}
