<?php

namespace Clevis\RestApi;


interface IApiAuthenticator
{

    /**
     * Receives API key and/or request data and returns an authenticated user entity or NULL
     *
     * @param string|int
     * @param \StdClass|array
     * @return IApiUser|NULL
     */
    function authenticate($apiKey, $requestData);

}
