<?php

namespace ondrs\ApiBase\Services;

use Nette\Reflection\ClassType;
use Nette\Utils\Strings;
use ondrs\ApiBase\ApiPresenter;
use ReflectionMethod;

class ApiDocBuilder
{

    /** @var SchemaProvider */
    private $schemaProvider;

    /** @var ExampleResponse  */
    private $exampleResponse;


    public function __construct(SchemaProvider $schemaProvider, ExampleResponse $exampleResponse)
    {
        $this->schemaProvider = $schemaProvider;
        $this->exampleResponse = $exampleResponse;
    }


    /**
     * @param ApiPresenter[] $presenters
     * @return array
     */
    public function buildApiDoc(array $presenters)
    {
        $doc = [];

        foreach ($presenters as $presenter) {
            $reflection = ClassType::from($presenter);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (Strings::startsWith($method->getName(), 'action')) {
                    $arr = $this->buildMethodDoc($presenter, $method->getName());

                    if ($arr['url'] === NULL) {
                        continue;
                    }

                    $doc[] = $arr;
                }
            }
        }

        return $doc;
    }


    /**
     * @param ApiPresenter $apiPresenter
     * @param string $method
     * @return array
     */
    public function buildMethodDoc(ApiPresenter $apiPresenter, $method)
    {
        $reflection = ClassType::from($apiPresenter)->getMethod($method);
        $actionName = ltrim($method, 'action');

        $schema = [
            SchemaProvider::REQUEST => SchemaProvider::getSchemaFile($apiPresenter, SchemaProvider::REQUEST, $actionName),
            SchemaProvider::RESPONSE => SchemaProvider::getSchemaFile($apiPresenter, SchemaProvider::RESPONSE, $actionName),
        ];

        foreach ($schema as $key => $schemaFile) {

            $schema[$key] = file_exists($schemaFile)
                ? [
                    'schema' => $this->schemaProvider->get($schemaFile),
                    'example' => $this->exampleResponse->generate($apiPresenter, $key, $actionName),
                ]
                : NULL;
        }

        $res = $reflection->getAnnotations();

        return [
            'url' => $reflection->getAnnotation('url'),
            'method' => strtoupper($reflection->getAnnotation('method')),
            'parameters' => isset($res['param']) ? $res['param'] : NULL,
            'description' => isset($res['description']) ? join(PHP_EOL, $res['description']) : NULL,
        ] + $schema;
    }

}
