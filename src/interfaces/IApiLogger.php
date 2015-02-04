<?php

namespace Clevis\RestApi;

use Nette\Http;
use Nette\Application;


interface IApiLogger
{

    /**
     * Takes request and response data and saves them to log
     *
     * @param Http\Request
     * @param Http\Response
     * @param Application\Request
     * @param Application\IResponse
     * @param string
     * @param IApiUser|NULL
     * @return void
     */
    function logRequest(
        Http\Request $httpRequest, Http\Response $httpResponse,
        Application\Request $request, ApiResponse $response,
        $requestBody,
        IApiUser $user = NULL);

}
