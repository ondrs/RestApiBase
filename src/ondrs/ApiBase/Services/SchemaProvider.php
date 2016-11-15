<?php

namespace ondrs\ApiBase\Services;

use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use ondrs\ApiBase\ApiPresenter;
use ReflectionClass;

class SchemaProvider
{
    const REQUEST = 'request';
    const RESPONSE = 'response';

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
     * @param ApiPresenter $apiPresenter
     * @param string $what
     * @param string $action
     * @return string
     */
    public static function getSchemaFile(ApiPresenter $apiPresenter, $what, $action)
    {
        $rf = new ReflectionClass(get_class($apiPresenter));
        $dir = dirname($rf->getFileName());

        $path = realpath("$dir/" . lcfirst($action) . ".$what.neon");

        return str_replace('\\', '/', $path);
    }


    /**
     * @internal
     * @param string $schemaFile
     * @return \stdClass
     */
    private static function getSchema($schemaFile)
    {
        $schema = @file_get_contents($schemaFile);  // TODO: validate content
        $schema = Neon::decode($schema);

        return Json::decode(Json::encode($schema));
    }
}
