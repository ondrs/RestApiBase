<?php

use Nette\Http;
use Nette\Application;
use Clevis\RestApi\IApiUser;
use Clevis\RestApi\ApiResponse;
use Clevis\RestApi\ApiPresenter;


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

class DummyUser implements \Clevis\RestApi\IApiUser
{

	public function getApiKey()
	{
		return 123456;
	}

}


class DummySuccessAuthenticator implements \Clevis\RestApi\IApiAuthenticator
{

	public function authenticate($apiKey, $requestData)
	{
		return new DummyUser;
	}

}


class DummyFailAuthenticator implements \Clevis\RestApi\IApiAuthenticator
{

	public function authenticate($apiKey, $requestData)
	{
		return NULL;
	}

}

