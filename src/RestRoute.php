<?php

namespace Clevis\RestApi;

use Nette\Application\Routers\Route;
use Nette\Http\IRequest;


/**
 * Route with HTTP verb matching
 */
class RestRoute extends Route
{

    const
        METHOD_GET = 4,
        METHOD_POST = 8,
        METHOD_PUT = 16,
        METHOD_PATCH = 32,
        METHOD_DELETE = 64,
        METHODS_ALL = 124,
        RESTFUL = 128,
        METHOD_OPTIONS = 256;


    protected static $restDictionary = array(
        'GET' => 'get', // returns a resource "GET /me/articles"
        'POST' => 'append', // appends a new item in the list of resources "POST me/articles"
        'PUT' => 'create', // creates or replaces a resource "PUT /me/articles/1"
        'PATCH' => 'update', // partialy modifies a resource "PATCH /me/artices/1"
        'DELETE' => 'delete', // deletes a resource "DELETE /me/articles/1"
    );


    public static function setRestDictionary(array $dictionary)
    {
        self::$restDictionary = array_merge(self::$restDictionary, $dictionary);
    }


    public function match(IRequest $httpRequest)
    {
        $appRequest = parent::match($httpRequest);
        if (!$appRequest) {
            return NULL;
        }

        $method = $httpRequest->getMethod();

        if (!in_array($method, array('GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'))) {
            return NULL;
        }

        if ($this->flags & self::RESTFUL) {
            $action = self::$restDictionary[$method];

            $params = $appRequest->getParameters();
            $params['action'] = $action;
            $appRequest->setParameters($params);

            return $appRequest;
        }

        if (($this->flags & self::METHOD_GET) === self::METHOD_GET && $method !== 'GET') {
            return NULL;
        }

        if (($this->flags & self::METHOD_POST) === self::METHOD_POST && $method !== 'POST') {
            return NULL;
        }

        if (($this->flags & self::METHOD_PUT) === self::METHOD_PUT && $method !== 'PUT') {
            return NULL;
        }

        if (($this->flags & self::METHOD_DELETE) === self::METHOD_DELETE && $method !== 'DELETE') {
            return NULL;
        }

        if (($this->flags & self::METHOD_PATCH) === self::METHOD_PATCH && $method !== 'PATCH') {
            return NULL;
        }

        if (($this->flags & self::METHOD_OPTIONS) === self::METHOD_OPTIONS && $method !== 'OPTIONS') {
            return NULL;
        }

        return $appRequest;
    }

}
