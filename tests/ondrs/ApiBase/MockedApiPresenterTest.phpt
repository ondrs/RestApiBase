<?php

use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/dummies/MockedDummyPresenter.php';


class MockedApiPresenterTest extends Tester\TestCase
{

    /** @var  \ondrs\ApiBase\ApiPresenter */
    private $apiPresenter;


    function setUp()
    {
        $this->apiPresenter = new MockedDummyPresenter();

        $schemaProvider = new \ondrs\ApiBase\SchemaProvider(new \Nette\Caching\Storages\DevNullStorage());
        $fakeResponse = new \ondrs\ApiBase\FakeResponse($schemaProvider);

        $this->apiPresenter->schemaValidatorFactory = new \ondrs\ApiBase\SchemaValidatorFactory($schemaProvider);
        $this->apiPresenter->fakeResponse = $fakeResponse;
    }



    function testActionMockedSchema()
    {
        $params = ['action' => 'mockedSchema'];
        $request = new \Nette\Application\Request('MockedDummy', \Nette\Http\IRequest::POST, $params);

        $response = $this->apiPresenter->run($request);

        Assert::same(\Nette\Http\IResponse::S200_OK, $response->getResponseCode());

        $p = $response->getPayload();
        Assert::type('string', $p->message);
        Assert::type('integer', $p->code);
        Assert::type('object', $p->params);
        Assert::type('string', $p->params->name);
        Assert::type('integer', $p->params->age);
        Assert::type('object', $p->params->address);
        Assert::type('array', $p->params->address->geo);
        Assert::count(2, $p->params->address->geo);
    }

}


run(new MockedApiPresenterTest());
