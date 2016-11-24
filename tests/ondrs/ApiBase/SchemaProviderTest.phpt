<?php

use Nette\Utils\Strings;
use ondrs\ApiBase\Services\SchemaProvider;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/dummies/DummyPresenter.php';


class SchemaProviderTest extends Tester\TestCase
{

    /** @var  \ondrs\ApiBase\Services\SchemaProvider */
    private $schemaProvider;


    function setUp()
    {
        $this->schemaProvider = new SchemaProvider(new \Nette\Caching\Storages\DevNullStorage());
    }


    function testGetSchemaFile()
    {
        $schemaFile = SchemaProvider::getSchemaFile(new DummyPresenter(), 'request', 'validSchema');

        Assert::true(Strings::endsWith($schemaFile, 'ondrs/ApiBase/dummies/validSchema.request.neon'));
    }


    function testGetDefinitions()
    {
        $schemaFile = __DIR__ . '/dummies/SchemaProviderTest.neon';

        $schema = $this->schemaProvider->get($schemaFile);

        Assert::same('object', $schema->properties->params->properties->address->properties->geo->type);
        Assert::same('array', $schema->properties->params->properties->address->properties->geo->properties->address->properties->geo->type);
    }


}


run(new SchemaProviderTest());
