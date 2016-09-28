<?php

namespace ondrs\ApiBase;

use Nette\Application\Routers\Route;
use Nette\Http\IRequest;

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
        IRequest::GET => 'get', // returns a resource "GET /me/articles"
        IRequest::POST => 'post', // appends a new item in the list of resources "POST me/articles"
        IRequest::PUT => 'put', // creates or replaces a resource "PUT /me/articles/1"
        IRequest::PATCH => 'patch', // partially modifies a resource "PATCH /me/artices/1"
        IRequest::DELETE => 'delete', // deletes a resource "DELETE /me/articles/1"
    );


    public static function setRestDictionary(array $dictionary)
    {
        self::$restDictionary = array_merge(self::$restDictionary, $dictionary);
    }


    /**
     * @param IRequest $httpRequest
     * @return \Nette\Application\Request|NULL
     */
    public function match(IRequest $httpRequest)
    {
        $appRequest = parent::match($httpRequest);

        if (!$appRequest) {
            return NULL;
        }

        $method = $httpRequest->getMethod();

        if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'])) {
            return NULL;
        }

        if ($this->getFlags() & self::RESTFUL) {
            $action = self::$restDictionary[$method];

            $params = $appRequest->getParameters();
            $params['action'] = $action;
            $appRequest->setParameters($params);

            return $appRequest;
        }

        if (($this->getFlags() & self::METHOD_GET) === self::METHOD_GET && $method !== IRequest::GET) {
            return NULL;
        }

        if (($this->getFlags() & self::METHOD_POST) === self::METHOD_POST && $method !== IRequest::POST) {
            return NULL;
        }

        if (($this->getFlags() & self::METHOD_PUT) === self::METHOD_PUT && $method !== IRequest::PUT) {
            return NULL;
        }

        if (($this->getFlags() & self::METHOD_DELETE) === self::METHOD_DELETE && $method !== IRequest::DELETE) {
            return NULL;
        }

        if (($this->getFlags() & self::METHOD_PATCH) === self::METHOD_PATCH && $method !== IRequest::PATCH) {
            return NULL;
        }

        if (($this->getFlags() & self::METHOD_OPTIONS) === self::METHOD_OPTIONS && $method !== IRequest::OPTIONS) {
            return NULL;
        }

        return $appRequest;
    }

}
