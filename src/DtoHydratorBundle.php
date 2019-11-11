<?php

namespace DtoHydrator;

use DtoHydrator\DependencyInjection\DtoHydratorExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class DtoHydratorBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new DtoHydratorExtension();
    }
}