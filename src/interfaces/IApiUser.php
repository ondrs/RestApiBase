<?php

namespace Clevis\RestApi;


interface IApiUser
{

    /**
     * Returns user's API key
     *
     * @return int|string
     */
    function getApiKey();

}
