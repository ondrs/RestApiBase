<?php

namespace ondrs\ApiBase;

use Nette\Reflection\ClassType;
use Nette\Utils\Strings;
use ReflectionMethod;

class ApiDocBuilder
{

    /** @var SchemaProvider */
    private $schemaProvider;

    /** @var FakeResponse */
    private $fakeResponse;


    public function __construct(SchemaProvider $schemaProvider, FakeResponse $fakeResponse)
    {
        $this->schemaProvider = $schemaProvider;
        $this->fakeResponse = $fakeResponse;
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
            'request' => SchemaProvider::getSchemaFile($apiPresenter, 'request', $actionName),
            'response' => SchemaProvider::getSchemaFile($apiPresenter, 'response', $actionName),
        ];

        foreach ($schema as $key => $schemaFile) {
            $schema[$key] = file_exists($schemaFile)
                ? [
                    'schema' => $this->schemaProvider->get($schemaFile),
                    'example' => $this->fakeResponse->generate($schemaFile),
                ]
                : NULL;
        }

        $res = $reflection->getAnnotations();

        return [
            'url' => $reflection->getAnnotation('url'),
            'parameters' => isset($res['param']) ? $res['param'] : NULL,
            'description' => isset($res['description']) ? join(PHP_EOL, $res['description']) : NULL,
            'request' => $schema['request'],
            'response' => $schema['response'],
        ];
    }

}
