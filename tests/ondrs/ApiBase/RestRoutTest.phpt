<?php

require __DIR__ . '/../../bootstrap.php';

use Tester\Assert;

use Nette\Http;
use Nette\Application;
use ondrs\ApiBase\RestRoute;


$url = new Http\UrlScript('http://doma.in/api/resource/1');
$request = new Http\Request(
    $url,
    NULL /*$query*/, NULL /*$post*/, NULL /*$files*/, NULL /*$cookies*/, NULL /*$headers*/,
    Http\Request::DELETE);

$route = new RestRoute('/api/resource/<id>', 'Resource:');

Assert::equal(
    new Application\Request('Resource', 'DELETE', ['id' => '1'], [], [], ['secured' => FALSE]),
    $route->match($request));

$restfulRoute = new RestRoute('/api/resource/<id>', 'Resource:', RestRoute::RESTFUL);

Assert::equal(
    new Application\Request('Resource', 'DELETE', ['id' => '1', 'action' => 'delete'], [], [], ['secured' => FALSE]),
    $restfulRoute->match($request));
