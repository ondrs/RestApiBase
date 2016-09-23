<?php

use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\UrlScript;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/dummies/DummyPresenter.php';


class ApiPresenterTest extends Tester\TestCase
{

    /** @var  \ondrs\ApiBase\ApiPresenter */
    private $apiPresenter;


    function setUp()
    {
        $user = \Mockery::mock(\Nette\Security\User::class);
        $linkGenerator = \Mockery::mock(\Nette\Application\LinkGenerator::class);

        // initial request data
        $url = new UrlScript;
        $httpRequest = new Request($url);
        $httpResponse = new Response;

        $this->apiPresenter = new DummyPresenter($user, $linkGenerator, $httpRequest, $httpResponse);
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


}


run(new ApiPresenterTest());
