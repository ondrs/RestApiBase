<?php

use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


class SchemaValidatorTest extends Tester\TestCase
{

    /** @var  \ondrs\ApiBase\Services\SchemaValidator */
    private $schemaValidator;


    function setUp()
    {
        $schemaProvider = new \ondrs\ApiBase\Services\SchemaProvider(new \Nette\Caching\Storages\DevNullStorage());

        $this->schemaValidator = new \ondrs\ApiBase\Services\SchemaValidator(__DIR__ . '/dummies/testing.schema.neon', $schemaProvider);
    }


    function testValidSchema()
    {
        $data = [
            'someText' => 'val',
            'someNumber' => 123,
            'nestedObject' => [
                'someText' => 'aaa',
                'someNumber' => 456,
            ]
        ];

        $valid = $this->schemaValidator->isValid($data);

        Assert::same(TRUE, $valid);
        Assert::equal([], $this->schemaValidator->getErrors());
    }


    function testINValidSchema()
    {
        $data = [
            'key' => 'val',
        ];

        $valid = $this->schemaValidator->isValid($data);

        Assert::same(FALSE, $valid);
        Assert::count(3, $this->schemaValidator->getErrors());
    }



}


run(new SchemaValidatorTest());
