<?php

namespace ondrs\ApiBase;

use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;

class SchemaProvider
{

    /** @var Cache */
    private $cache;

    /** @var array */
    private $memory = [];


    public function __construct(IStorage $storage)
    {
        $this->cache = new Cache($storage);
    }


    /**
     * @param string $schemaFile
     * @return \stdClass
     */
    public function get($schemaFile)
    {
        if (!isset($this->memory[$schemaFile])) {
            $this->memory[$schemaFile] = $this->cache->load($schemaFile, function (& $dependencies) use ($schemaFile) {
                $dependencies[Cache::FILES] = $schemaFile;

                return self::getSchema($schemaFile);
            });
        }

        return $this->memory[$schemaFile];
    }


    /**
     * @internal
     * @param string $schemaFile
     * @return \stdClass
     */
    private static function getSchema($schemaFile)
    {
        $schema = FileSystem::read($schemaFile);
        $schema = Neon::decode($schema);

        return Json::decode(Json::encode($schema));
    }
}
