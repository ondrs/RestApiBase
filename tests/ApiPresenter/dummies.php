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




class DummyAuthenticator implements IAuthenticator
{

    public function authenticate(array $credentials)
    {
        return new \Nette\Security\Identity(1);
    }

}


