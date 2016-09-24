<?php

use Nette\Utils\Strings;
use ondrs\ApiBase\SchemaProvider;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/dummies/DummyPresenter.php';


class SchemaProviderTest extends Tester\TestCase
{

    /** @var  SchemaProvider */
    private $schemaProvider;


    function setUp()
    {
        $this->schemaProvider = new SchemaProvider(new \Nette\Caching\Storages\DevNullStorage());
    }


    function testGetSchemaFile()
    {
        $schemaFile = SchemaProvider::getSchemaFile(new DummyPresenter(), 'request', 'validSchema');

        Assert::true(Strings::endsWith($schemaFile, 'RestApiBase/tests/ondrs/ApiBase/dummies/validSchema.request.neon'));
    }


}


run(new SchemaProviderTest());
