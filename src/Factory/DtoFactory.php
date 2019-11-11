<?php

namespace DtoHydrator\Factory;

use DtoHydrator\Dto\AbstractHydrator;
use DtoHydrator\Dto\Dehydrator;
use DtoHydrator\Dto\Hydrator;

final class DtoFactory
{
    /** @var Hydrator $hydrator */
    protected $hydrator;

    /** @var Dehydrator $deHydrator */
    protected $deHydrator;

    /**
     * Factory constructor.
     *
     * @param Hydrator   $hydrator
     * @param Dehydrator $deHydrator
     */
    public function __construct(Hydrator $hydrator, Dehydrator $deHydrator)
    {
        $this->hydrator   = $hydrator;
        $this->deHydrator = $deHydrator;
    }


    /**
     * @param string $class
     * @return AbstractHydrator
     */
    public function make(string $class) : AbstractHydrator
    {
        switch ($class)
        {
            case Hydrator::class:
                return $this->hydrator;
            case Dehydrator::class:
                return $this->deHydrator;
            default:
                throw new \InvalidArgumentException("No hydrator with class : {$class} found");
        }
    }
}