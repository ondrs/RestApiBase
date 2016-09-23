<?php

namespace ondrs\ApiBase;

use JsonSchema\Validator;
use Nette\Caching\Cache;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;

class SchemaValidator
{

    /** @var Validator */
    private $validator;

    /** @var \stdClass */
    private $schema;


    public function __construct($schemaFile, Cache $cache)
    {
        $this->validator = new Validator;

        $this->schema = $cache->load($schemaFile, function (& $dependencies) use ($schemaFile) {
            $dependencies[Cache::FILES] = $schemaFile;

            return self::getSchema($schemaFile);
        });
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
     * @param string $schemaFile
     * @return \stdClass
     */
    public static function getSchema($schemaFile)
    {
        $schema = FileSystem::read($schemaFile);
        $schema = Neon::decode($schema);

        return Json::decode(Json::encode($schema));
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->validator->getErrors();
    }
}
