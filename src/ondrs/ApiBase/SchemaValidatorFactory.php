<?php

namespace ondrs\ApiBase;

use Nette\Caching\Cache;
use Nette\Caching\IStorage;

class SchemaValidatorFactory
{

    /** @var Cache */
    private $cache;


    public function __construct(IStorage $storage)
    {
        $this->cache = new Cache($storage, __CLASS__);
    }


    /**
     * @param string $schemaFile
     * @return SchemaValidator
     */
    public function create($schemaFile)
    {
        return new SchemaValidator($schemaFile, $this->cache);
    }
}
