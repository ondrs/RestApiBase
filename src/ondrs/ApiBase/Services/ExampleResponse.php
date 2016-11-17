<?php

namespace ondrs\ApiBase\Services;

use Nette\Neon\Neon;
use Nette\Utils\Json;
use ondrs\ApiBase\ApiPresenter;

class ExampleResponse
{

    /** @var  FakeResponse */
    private $fakeResponse;


    public function __construct(FakeResponse $fakeResponse)
    {
        $this->fakeResponse = $fakeResponse;
    }


    /**
     * @param ApiPresenter $presenter
     * @param string $what
     * @param string $actionName
     * @return mixed|\stdClass
     */
    public function generate(ApiPresenter $presenter, $what, $actionName)
    {
        $schemaFile = SchemaProvider::getSchemaFile($presenter, $what, $actionName);
        $exampleFile = SchemaProvider::getSchemaExampleFile($presenter, $what, $actionName);

        if ($exampleFile) {
            $example = @file_get_contents($exampleFile);  // TODO: validate content
            $example = Neon::decode($example);
            return Json::decode(Json::encode($example));
        }

        return $this->fakeResponse->generate($schemaFile);
    }

}
