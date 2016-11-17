<?php

namespace ondrs\ApiBase\DI;

use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use ondrs\ApiBase\Services\ApiDocBuilder;
use ondrs\ApiBase\Services\ExampleResponse;
use ondrs\ApiBase\Services\FakeResponse;
use ondrs\ApiBase\Services\SchemaProvider;
use ondrs\ApiBase\Services\SchemaValidatorFactory;

class Extension extends CompilerExtension
{

    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('apiDocBuilder'))
            ->setClass(ApiDocBuilder::class);

        $builder->addDefinition($this->prefix('fakeResponse'))
            ->setClass(FakeResponse::class);

        $builder->addDefinition($this->prefix('exampleResponse'))
            ->setClass(ExampleResponse::class);

        $builder->addDefinition($this->prefix('schemaProvider'))
            ->setClass(SchemaProvider::class);

        $builder->addDefinition($this->prefix('schemaValidatorFactory'))
            ->setClass(SchemaValidatorFactory::class);

    }


    /**
     * @param Configurator $configurator
     */
    public static function register(Configurator $configurator)
    {
        $configurator->onCompile[] = function ($config, Compiler $compiler) {
            $compiler->addExtension('ApiBase', new Extension());
        };
    }

}
