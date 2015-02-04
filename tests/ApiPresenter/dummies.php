<?php

use Nette\Http;
use Nette\Application;
use Clevis\RestApi\ApiResponse;
use Clevis\RestApi\ApiPresenter;
use Nette\Security\IAuthenticator;
use Nette\Security\IUserStorage;


class DummyPresenter extends ApiPresenter
{

    public function startup()
    {
        $this->minApiVersion = 1;
        $this->maxApiVersion = 2;
        $this->checkSsl = TRUE;
    }

    public function actionGet()
    {
        global $getCalled;

        $getCalled = TRUE;
        $this->payload = array(123, 456);
    }

    public function actionPost()
    {
        global $postCalled;

        $postCalled = TRUE;
        $this->sendErrorResponse(ApiResponse::S403_FORBIDDEN, 'forbidden');
    }

    protected function getRawPostData()
    {
        return '{"key": "value"}';
    }

}


class DummyUserStorage implements IUserStorage
{

    function setAuthenticated($state)
    {

    }

    function isAuthenticated()
    {
        return TRUE;
    }


    function setIdentity(\Nette\Security\IIdentity $identity = NULL)
    {

    }

    function getIdentity()
    {
        return NULL;
    }


    function setExpiration($time, $flags = 0)
    {

    }

    function getLogoutReason()
    {
        return 1;
    }
}


class DummySuccessAuthenticator implements IAuthenticator
{

    public function authenticate(array $credentials)
    {
        return new \Nette\Security\User(new DummyUserStorage);
	}

}


class DummyFailAuthenticator implements IAuthenticator
{

    public function authenticate(array $credentials)
    {
        return NULL;
    }

}

