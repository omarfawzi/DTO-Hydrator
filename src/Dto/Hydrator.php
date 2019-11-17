<?php

namespace DtoHydrator\Dto;

use Doctrine\Common\Annotations\AnnotationException;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class Hydrator extends AbstractHydrator
{
    /**
     * <p> Hydrates a DTO , i.e Entity -> Dto </p>
     *
     * @param object $source      <p>
     *                            The $source parameter must represent an Entity
     *                            </p>
     * @param object $destination <p>
     *                            The $destination parameter must represent a DTO
     *                            </p>
     * @param array  $context
     * @throws AnnotationException
     */
    public function hydrate(object $source, object $destination, array $context = []) : void
    {
        $this->hydrateOutput($source, $destination, $context);
    }

    /**
     * @param object $source      <p>
     *                            The $source parameter must represent an Entity
     *                            </p>
     * @param object $destination <p>
     *                            The $destination parameter must represent a DTO
     *                            </p>
     * @param array  $context
     * @throws AnnotationException
     */
    protected function hydrateOutput(object $source, object $destination, array $context = [])
    {
        $destinationAttributes = $this->getAttributes($destination);

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        foreach ($destinationAttributes as $attribute) {
            if (property_exists($source,$attribute)) {
                $propertyAccessor->setValue($destination, $attribute, $propertyAccessor->getValue($source, $attribute));
            }
        }
    }
}