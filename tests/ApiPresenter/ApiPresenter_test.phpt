<?php

require __DIR__ . '/../boot.php';
require __DIR__ . '/dummies.php';

use Tester\Assert;
use Nette\Http;
use Nette\Application;
use Clevis\RestApi\RestRoute;


// initial request data
$url = new Http\UrlScript;
$httpRequest = new Http\Request($url);
$httpResponse = new Http\Response;

$presenter = new DummyPresenter;
$presenter->injectPrimary(
	new Nette\DI\Container,
	new Http\Context($httpRequest, $httpResponse),
	$httpRequest,
	$httpResponse
);

$params = array('action' => 'patch');
$request = new Application\Request('Some', 'patch', $params);

// -> fails on SSL
$response = $presenter->run($request);
Assert::same(403, $response->getResponseCode());



$post = array();
$files = array();
// set SSL flag
$flags = array(Application\Request::SECURED => TRUE);
$request = new Application\Request('Dummy', 'patch', $params, $post, $files, $flags);

// -> fails on API version (none/low)
$response = $presenter->run($request);
Assert::same(426, $response->getResponseCode());
Assert::same('Minimal', substr($response->payload['message'], 0, 7));



// set X-Api-Version header
$httpRequest = new Http\Request($url, NULL, NULL, NULL, NULL, array('x-api-version' => '3'));
$presenter = new DummyPresenter;
$presenter->injectPrimary(
	new Nette\DI\Container,
	new Http\Context($httpRequest, $httpResponse),
	$httpRequest,
	$httpResponse
);

// -> fails on API version (high)
$response = $presenter->run($request);
Assert::same(426, $response->getResponseCode());
Assert::same('Maximal', substr($response->payload['message'], 0, 7));



// set valid X-Api-Version header
$httpRequest = new Http\Request($url, NULL, NULL, NULL, NULL, array('x-api-version' => '1'));
$presenter = new DummyPresenter;
$presenter->injectPrimary(
	new Nette\DI\Container,
	new Http\Context($httpRequest, $httpResponse),
	$httpRequest,
	$httpResponse
);
// set failing authorizator
$presenter->setAuthenticator(new DummyFailAuthenticator);

// -> fails on authorization
$response = $presenter->run($request);
Assert::same(405, $response->getResponseCode());



// set success authorizator
$presenter->setAuthenticator(new DummySuccessAuthenticator);

// -> fails on method
$response = $presenter->run($request);
Assert::same(405, $response->getResponseCode());



// call supported method
$params = array('action' => 'get');
$request = new Application\Request('Dummy', 'get', $params, $post, $files, $flags);

// -> success
$response = $presenter->run($request);
Assert::same(200, $response->getResponseCode());
Assert::same(array(123, 456), $response->payload);
