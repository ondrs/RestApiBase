<?php

class MockedDummyPresenter extends \ondrs\ApiBase\ApiPresenter
{

    protected $mockResponses = TRUE;


    protected function getRequestBody()
    {
        return file_get_contents(__DIR__ . '/requestBody.json');
    }

    public function actionMockedSchema()
    {

    }
}
