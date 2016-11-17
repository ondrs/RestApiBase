<?php

namespace ondrs\ApiBase;

use Nette\Application\BadRequestException;

class ApiBadRequestException extends BadRequestException
{

}


class JsonSchemaException extends ApiBadRequestException
{

    /** @var array */
    public $errors = [];
}
