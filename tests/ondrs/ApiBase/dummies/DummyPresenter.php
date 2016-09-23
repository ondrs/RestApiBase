<?php

class DummyPresenter extends \ondrs\ApiBase\ApiPresenter
{

    public function getRequestBody()
    {
        return file_get_contents(__DIR__ . '/requestBody.json');
    }

    public function actionDefault()
    {
        return ['result' => 'ok'];
    }


    public function actionEmpty()
    {

    }


    public function actionContent()
    {
        return $this->body->c;
    }


    public function actionWithArgs($a, $b = NULL, $c = 1, $d)
    {

    }

}
