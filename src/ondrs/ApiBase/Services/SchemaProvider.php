<?php

namespace ondrs\ApiBase\Services;

use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Neon\Neon;
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

                return $this->getSchema($schemaFile);
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
     * @param ApiPresenter $apiPresenter
     * @param string $what
     * @param string $action
     * @return string|NULL
     */
    public static function getSchemaExampleFile(ApiPresenter $apiPresenter, $what, $action)
    {
        $rf = new ReflectionClass(get_class($apiPresenter));
        $dir = dirname($rf->getFileName());

        $path = realpath("$dir/" . lcfirst($action) . ".$what.example.neon");

        if (is_file($path)) {
            return str_replace('\\', '/', $path);
        }

        return NULL;
    }


    /**
     * @param string $currentFile
     * @param array $schema
     * @return array
     */
    private function getDefinitions($currentFile, array $schema)
    {
        foreach ($schema as $key => $value) {

            if (is_array($value)) {
                $schema[$key] = $this->getDefinitions($currentFile, $value);

            } else if ($key === '$ref') {
                $fileInfo = new \SplFileInfo($currentFile);
                $definitionFile = str_replace('#', $fileInfo->getPath(), $value);

                unset($schema[$key]);

                $schema = array_merge($schema, (array)$this->get($definitionFile));
            }
        }

        return $schema;
    }


    /**
     * @internal
     * @param string $schemaFile
     * @return \stdClass
     */
    private function getSchema($schemaFile)
    {
        $data = @file_get_contents($schemaFile);  // TODO: validate content
        $data = Neon::decode($data);

        $schema = Json::decode(Json::encode($data), Json::FORCE_ARRAY);
        $schema = $this->getDefinitions($schemaFile, $schema);

        return Json::decode(Json::encode($schema));
    }
}
