<?php

namespace ondrs\ApiBase\Services;

use JSONSchemaFaker\Faker;

class FakeResponse
{

    /** @var SchemaProvider */
    private $schemaProvider;


    public function __construct(SchemaProvider $schemaProvider)
    {
        $this->schemaProvider = $schemaProvider;
    }


    /**
     * @param string $schemaFile
     * @return \stdClass
     */
    public function generate($schemaFile)
    {
        $schema = $this->schemaProvider->get($schemaFile);

        return Faker::fake($schema);
    }
}
