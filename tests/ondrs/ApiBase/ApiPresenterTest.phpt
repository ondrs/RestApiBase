<?php

use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/dummies/DummyPresenter.php';


class ApiPresenterTest extends Tester\TestCase
{

    /** @var  \ondrs\ApiBase\ApiPresenter */
    private $apiPresenter;


    function setUp()
    {
        $this->apiPresenter = new DummyPresenter;

        $schemaProvider = new \ondrs\ApiBase\SchemaProvider(new \Nette\Caching\Storages\DevNullStorage());
        $this->apiPresenter->schemaProvider = $schemaProvider;
        $this->apiPresenter->schemaValidatorFactory = new \ondrs\ApiBase\SchemaValidatorFactory($schemaProvider);

        $fakeResponse = new \ondrs\ApiBase\FakeResponse($schemaProvider);
        $this->apiPresenter->fakeResponse = $fakeResponse;
    }


    function testFilterData()
    {
        $date = new DateTime('2016-01-01 12:00');

        $data = [
            'a' => 'string',
            'b' => 2,
            'c' => [
                'ca' => 'string',
                'cb' => 3,
                'cc' => [
                    'cca' => 1,
                    'ccb' => $date,
                ],
                'cd' => $date,
            ],
            'e' => $date,
        ];

        $expected = [
            'a' => 'string',
            'b' => 2,
            'c' => [
                'ca' => 'string',
                'cb' => 3,
                'cc' => [
                    'cca' => 1,
                    'ccb' => '2016-01-01T12:00:00+01:00',
                ],
                'cd' => '2016-01-01T12:00:00+01:00',
            ],
            'e' => '2016-01-01T12:00:00+01:00',
        ];

        Assert::same($expected, \ondrs\ApiBase\ApiPresenter::filterData($data));
    }


    function testActionDefault()
    {
        $params = ['action' => 'default'];
        $request = new \Nette\Application\Request('Dummy', \Nette\Http\IRequest::POST, $params);

        $response = $this->apiPresenter->run($request);

        Assert::same(\Nette\Http\IResponse::S200_OK, $response->getResponseCode());
        Assert::same(['result' => 'ok'], $response->getPayload());
    }


    function testActionEmpty()
    {
        $params = ['action' => 'empty'];
        $request = new \Nette\Application\Request('Dummy', \Nette\Http\IRequest::POST, $params);

        $response = $this->apiPresenter->run($request);

        Assert::same(\Nette\Http\IResponse::S200_OK, $response->getResponseCode());
        Assert::same([], $response->getPayload());
    }


    function testActionContent()
    {
        $params = ['action' => 'content'];
        $request = new \Nette\Application\Request('Dummy', \Nette\Http\IRequest::POST, $params);

        $response = $this->apiPresenter->run($request);

        $expected = (object)[
            'ca' => 2,
            'cb' => 'string',
        ];

        Assert::same(\Nette\Http\IResponse::S200_OK, $response->getResponseCode());
        Assert::equal($expected, $response->getPayload());
    }


    function testNonExistingAction()
    {
        Assert::exception(function () {
            $params = ['action' => 'boo'];
            $request = new \Nette\Application\Request('Dummy', \Nette\Http\IRequest::POST, $params);

            $response = $this->apiPresenter->run($request);
        }, \Nette\Application\BadRequestException::class, 'Method POST is not allowed.', 405);
    }


    function testActionWithArgs()
    {
        Assert::exception(function () {
            $params = ['action' => 'withArgs'];
            $request = new \Nette\Application\Request('Dummy', \Nette\Http\IRequest::GET, $params);

            $response = $this->apiPresenter->run($request);
        }, \Nette\Application\BadRequestException::class, "Missing parameter(s) 'a, d'.", 400);
    }


    function testActionValidSchema()
    {
        $params = ['action' => 'validSchema'];
        $request = new \Nette\Application\Request('Dummy', \Nette\Http\IRequest::POST, $params);

        $response = $this->apiPresenter->run($request);

        Assert::same(\Nette\Http\IResponse::S200_OK, $response->getResponseCode());
    }


    function testActionInvalidSchema()
    {
        Assert::exception(function () {
            $params = ['action' => 'invalidSchema'];
            $request = new \Nette\Application\Request('Dummy', \Nette\Http\IRequest::POST, $params);
            $response = $this->apiPresenter->run($request);
        }, \Nette\Application\BadRequestException::class, NULL, 400);
    }


    function testActionSchemaDoc()
    {
        $params = ['action' => 'apiDoc', 'method' => 'validSchema'];
        $request = new \Nette\Application\Request('Dummy', \Nette\Http\IRequest::POST, $params);
        $response = $this->apiPresenter->run($request);

        $p = $response->getPayload();
        Assert::type('object', $p['request']['schema']);
        Assert::type('object', $p['request']['example']);
        Assert::type('object', $p['response']['schema']);
        Assert::type('object', $p['response']['example']);
        Assert::type('string', $p['description']);
        Assert::type('string', $p['url']);
        Assert::type('array', $p['parameters']);
    }


    function testNotExistingActionApiDoc()
    {
        Assert::exception(function() {
            $params = ['action' => 'apiDoc', 'method' => 'empty'];
            $request = new \Nette\Application\Request('Dummy', \Nette\Http\IRequest::POST, $params);
            $response = $this->apiPresenter->run($request);
        }, \Nette\Application\BadRequestException::class, "No API documentation exists for the method 'empty'.", 404);
    }


}


run(new ApiPresenterTest());
