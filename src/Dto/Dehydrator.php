<?php

namespace DtoHydrator\Dto;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\ORMException;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Throwable;

final class Dehydrator extends AbstractHydrator
{
    /** @var EntityManagerInterface $entityManager */
    protected $entityManager;

    /** @var RequestStack $requestStack */
    protected $requestStack;

    /**
     * ShippingAddressInputTransformer constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param RequestStack           $requestStack
     */
    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack  = $requestStack;
    }

    /**
     * <p> Hydrates an Entity , i.e Dto -> Entity </p>
     *
     * @param object $source            <p>
     *                                  The $source parameter must represent a DTO
     *                                  </p>
     * @param object $destination       <p>
     *                                  The $destination parameter must represent an Entity
     *                                  </p>
     * @param array  $context
     * @throws AnnotationException
     * @throws ORMException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function hydrate(object $source, object $destination, array $context = []) : void
    {
        $this->hydrateInput($source, $destination, $this->requestStack->getCurrentRequest(), $context);
    }

    /**
     * @param object $current
     * @param object $destination
     * @param array  $requestAttribute
     * @param string $attribute
     * @param array  $context
     * @return object|null
     * @throws AnnotationException
     * @throws ORMException
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function captureReference(
        object $current,
        object $destination,
        array $requestAttribute,
        string $attribute,
        array $context
    ) {
        $reference = null;
        /** @var ReflectionExtractor $reflectionExtractor */
        $reflectionExtractor = new ReflectionExtractor();
        $propertyAccessor    = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();
        if (isset($requestAttribute['id'])) {

            /** @var string $entityName */
            $entityName = $reflectionExtractor->getTypes(get_class($destination), $attribute)[0]->getClassName();

            /** @var int $id */
            $id = $propertyAccessor->getValue($current, 'id');

            // Get a reference from entity manager for the embedded object
            $reference = $this->entityManager->getReference(
                $entityName,
                $id
            );

            /** @var Request $newRequest */
            $newRequest = new Request();

            array_walk(
                $requestAttribute,
                function ($value, $key) use ($newRequest) {
                    $newRequest->request->set($key, $value);
                }
            );

            // php recursion , fasten the belt
            $this->hydrateInput(
                $current,
                $reference,
                $newRequest,
                $context
            );
        }

        return $reference;
    }

    /**
     * @param object  $source           <p>
     *                                  The $source parameter must represent a DTO
     *                                  </p>
     * @param object  $destination      <p>
     *                                  The $destination parameter must represent an Entity
     *                                  </p>
     * @param Request $request
     * @param array   $context
     * @throws AnnotationException
     * @throws ORMException
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function hydrateInput(object $source, object $destination, Request $request, array $context = [])
    {
        $requestAttributes     = $request->request->all();
        $sourceAttributes      = array_intersect(
            array_keys($requestAttributes),
            $this->getAttributes($source, $context)
        );

        $destinationAttributes = array_intersect($sourceAttributes, $this->getAttributes($destination));
        $annotationReader      = new AnnotationReader();
        $reflectionClass       = new ReflectionClass($destination);

        $propertyAccessor      = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        foreach ($destinationAttributes as $attribute) {

            $reflectionProperty = $reflectionClass->getProperty($attribute);

            $setValue           = $propertyAccessor->getValue($source, $attribute);

            if (isset($requestAttributes[$attribute])) {

                if ($annotationReader->getPropertyAnnotation($reflectionProperty, new OneToMany()) !== null) {
                    $values = null;
                    foreach ($requestAttributes[$attribute] as $index => $requestAttribute) {
                        /** @var object $current */
                        $current  = $propertyAccessor->getValue($source, $attribute)[$index];

                        $values[] = $this->captureReference(
                                $current,
                                $destination,
                                $requestAttribute,
                                $attribute,
                                $context
                            ) ?? $current;
                    }

                    $setValue = $values ?? $setValue;

                } elseif($annotationReader->getPropertyAnnotation($reflectionProperty, new ManyToOne()) !== null) {
                    /** @var object $current */
                    $current  = $propertyAccessor->getValue($source, $attribute);

                    $setValue = $this->captureReference(
                            $current,
                            $destination,
                            $requestAttributes[$attribute],
                            $attribute,
                            $context
                        ) ?? $current;
                }
            }
            $propertyAccessor->setValue(
                $destination,
                $attribute,
                $setValue
            );


        }
    }
}