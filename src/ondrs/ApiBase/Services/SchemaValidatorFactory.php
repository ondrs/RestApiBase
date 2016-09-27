<?php

namespace ondrs\ApiBase\Services;

class SchemaValidatorFactory
{

    /** @var SchemaProvider */
    private $schemaProvider;


    public function __construct(SchemaProvider $schemaProvider)
    {
        $this->schemaProvider = $schemaProvider;
    }


    /**
     * @param string $schemaFile
     * @return SchemaValidator
     */
    public function create($schemaFile)
    {
        return new SchemaValidator($schemaFile, $this->schemaProvider);
    }
}
