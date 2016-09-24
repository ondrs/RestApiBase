<?php

namespace ondrs\ApiBase;

use JsonSchema\Validator;
use Nette\Utils\Json;

class SchemaValidator
{

    /** @var Validator */
    private $validator;

    /** @var \stdClass */
    private $schema;


    public function __construct($schemaFile, SchemaProvider $schemaProvider)
    {
        $this->validator = new Validator;
        $this->schema = $schemaProvider->get($schemaFile);
    }


    /**
     * @param array|\stdClass $data
     * @return bool
     */
    public function isValid($data)
    {
        $this->validator->check(Json::decode(Json::encode($data)), $this->schema);

        return $this->validator->isValid();
    }


    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->validator->getErrors();
    }
}
