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


    /**
     * Private description
     *
     * @description Description for API Doc
     * @url /super/url/to/valid/schema
     *
     * @param int $number
     * @param string $string
     * @param NULL|string $null
     * @return array
     */
    public function actionValidSchema($number = 1, $string = 'aa', $null = NULL)
    {
        return [
            'message' => 'string',
        ];
    }


    public function actionInvalidSchema()
    {
        return [
            'message' => 'string',
        ];
    }

}
