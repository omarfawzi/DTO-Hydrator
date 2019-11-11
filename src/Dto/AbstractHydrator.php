<?php

namespace DtoHydrator\Dto;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\Extractor\SerializerExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;

abstract class AbstractHydrator
{

    /**
     * @param object $source
     * @param object $destination
     * @param array  $context
     */
    abstract public function hydrate(object $source, object $destination, array $context = []) : void ;

    /**
     * @param object $class
     * @param array  $context
     * @return array|string[]|void|null
     * @throws AnnotationException
     */
    final protected function getAttributes(object $class, array $context = [])
    {
        if (isset($context['groups'])) {
            $serializerClassMetadataFactory = new ClassMetadataFactory(
                new AnnotationLoader(new AnnotationReader())
            );
            $serializerExtractor            = new SerializerExtractor($serializerClassMetadataFactory);

            return $serializerExtractor->getProperties(get_class($class), ['serializer_groups' => $context['groups']]);
        } else {
            $reflectionClass = new ReflectionExtractor();

            return $reflectionClass->getProperties($class);
        }
    }
}