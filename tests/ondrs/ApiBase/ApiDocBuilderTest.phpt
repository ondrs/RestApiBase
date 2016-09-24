<?php

use Nette\Utils\Strings;
use ondrs\ApiBase\SchemaProvider;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/dummies/DummyPresenter.php';
require_once __DIR__ . '/dummies/MockedDummyPresenter.php';


class ApiDocBuilderTest extends Tester\TestCase
{

    /** @var  \ondrs\ApiBase\ApiDocBuilder */
    private $apiDocBuilder;


    function setUp()
    {
        $schemaProvider = new SchemaProvider(new \Nette\Caching\Storages\DevNullStorage());
        $fakeResponse = new \ondrs\ApiBase\FakeResponse($schemaProvider);

        $this->apiDocBuilder = new \ondrs\ApiBase\ApiDocBuilder($schemaProvider, $fakeResponse);
    }


    function testBuildMethodDoc()
    {
        $doc = $this->apiDocBuilder->buildMethodDoc(new DummyPresenter(), 'actionValidSchema');

        Assert::type('object', $doc['request']['schema']);
        Assert::type('object', $doc['request']['example']);
        Assert::type('object', $doc['response']['schema']);
        Assert::type('object', $doc['response']['example']);
        Assert::type('string', $doc['description']);
        Assert::type('string', $doc['url']);
        Assert::type('array', $doc['parameters']);
    }


    function testBuildApiDoc()
    {
        $arr = [
            new DummyPresenter,
            new MockedDummyPresenter,
        ];

        $doc = $this->apiDocBuilder->buildApiDoc($arr);

        Assert::count(1, $doc);

        $p = $doc[0];

        Assert::type('object', $p['request']['schema']);
        Assert::type('object', $p['request']['example']);
        Assert::type('object', $p['response']['schema']);
        Assert::type('object', $p['response']['example']);
        Assert::type('string', $p['description']);
        Assert::type('string', $p['url']);
        Assert::type('array', $p['parameters']);
    }


}


run(new ApiDocBuilderTest());
