<?php

namespace ondrs\ApiBase;

use Nette;
use Nette\Application\BadRequestException;
use Nette\Http;
use Nette\Application\Request;
use DateTime;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use ReflectionClass;


abstract class ApiPresenter implements Nette\Application\IPresenter
{
    const ERROR_METHOD_IS_NOT_ALLOWED = 'Method %s is not allowed.';
    const ERROR_INVALID_JSON_DATA = 'Invalid JSON data.';
    const ERROR_JSON_SCHEMA = "JSON does not validate against schema.\n%s";
    const ERROR_INVALID_PARAMETER = "Invalid parameter '%s': '%s'.";
    const ERROR_MISSING_PARAMETER = "Missing parameter(s) '%s'.";
    const ERROR_NO_API_DOC = "No API documentation exists for the method '%s'.";

    /** @var bool */
    public static $mockAllResponses = FALSE;
    protected $mockResponses = FALSE;

    /** @var SchemaValidatorFactory @inject */
    public $schemaValidatorFactory;

    /** @var FakeResponse @inject */
    public $fakeResponse;

    /** @var ApiDocBuilder */
    public $apiDocBuilder;


    /** @var Request */
    protected $request;

    /** @var string */
    protected $rawBody;

    /** @var \stdClass|NULL */
    protected $body;


    protected function startup()
    {
        if (self::$mockAllResponses) {
            $this->mockResponses = TRUE;
        }
    }


    /**
     * {@inheritdoc}
     */
    public function run(Request $request)
    {
        $this->request = $request;

        $this->startup();

        $action = isset($this->request->parameters['action']) ? $this->request->parameters['action'] : 'default';

        if ($request->isMethod(Http\IRequest::POST) || $request->isMethod(Http\IRequest::PUT) || $request->isMethod(Http\IRequest::PATCH)) {
            $this->rawBody = $this->getRequestBody();
            $this->body = $this->parseRequestBody($this->rawBody);
            $this->validate('request', $action, $this->body);
        }

        if ($this->mockResponses) {
            $data = $this->fakeResponse->generate(SchemaProvider::getSchemaFile($this, 'response', $action));

        } else {
            $data = $this->dispatch($request, $action);

            if (!$data) {
                $data = [];
            }

            $data = self::filterData($data);
        }

        $this->validate('response', $action, $data);

        return new ApiResponse($data, Http\IResponse::S200_OK);
    }


    /**
     * @param string $what
     * @param string $action
     * @param \stdClass $data
     * @throws BadRequestException
     */
    public function validate($what, $action, $data)
    {
        $schemaFile = SchemaProvider::getSchemaFile($this, $what, $action);

        if (!file_exists($schemaFile)) {
            return;
        }

        $validator = $this->schemaValidatorFactory->create($schemaFile);

        if ($validator->isValid($data) === FALSE) {
            $errors = Json::encode($validator->getErrors(), Json::PRETTY);
            $this->error(sprintf(self::ERROR_JSON_SCHEMA, $errors), Http\IResponse::S400_BAD_REQUEST);
        }
    }


    /**
     * @param $method
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    public function argsToParams($method, array $args)
    {
        $rm = new \ReflectionMethod($this, $method);

        $params = [];
        $missingParams = [];

        foreach ($rm->getParameters() as $param) {
            $name = $param->getName();

            if (isset($args[$name])) {
                $params[$name] = $args[$name];
            } else if ($param->isDefaultValueAvailable()) {
                $params[$name] = $param->getDefaultValue();
            } else {
                $missingParams[] = $param->name;
            }

        }

        if ($missingParams) {
            $this->error(sprintf(self::ERROR_MISSING_PARAMETER, join(', ', $missingParams)), Http\IResponse::S400_BAD_REQUEST);
        }

        return $params;
    }


    /**
     * @param Request $request
     * @param $action
     * @return mixed
     */
    protected function dispatch(Request $request, $action)
    {
        $method = 'action' . $action;

        if (!method_exists($this, $method)) {
            $this->error(sprintf(self::ERROR_METHOD_IS_NOT_ALLOWED, strtoupper($request->method)), Http\IResponse::S405_METHOD_NOT_ALLOWED);
        }

        return call_user_func_array([$this, $method], $this->argsToParams($method, $request->parameters));
    }


    /**
     * @return string
     */
    public function getRequestBody()
    {
        return file_get_contents('php://input');
    }


    /**
     * @param $data
     * @return array
     */
    protected function parseRequestBody($data)
    {
        try {
            return Json::decode($data);
        } catch (JsonException $e) {
            $this->error(self::ERROR_INVALID_JSON_DATA, Http\IResponse::S400_BAD_REQUEST);
            exit; // IDE shut up
        }
    }


    /**
     * @param array $data
     * @return array
     */
    public static function filterData($data)
    {
        foreach ($data as $key => $value) {
            if ($value instanceof DateTime) {
                $data[$key] = $value->format(DATE_RFC3339);

            } elseif (is_array($value)) {
                $data[$key] = self::filterData($value);
            }
        }

        return $data;
    }


    /**
     * @param string $method
     * @return array
     */
    public function actionApiDoc($method)
    {
        $fullMethodName = 'action' . $method;
        $reflection = new Nette\Reflection\ClassType($this);

        if (!$reflection->hasMethod($fullMethodName)) {
            $this->error(sprintf(self::ERROR_METHOD_IS_NOT_ALLOWED, strtoupper($method)), Http\IResponse::S405_METHOD_NOT_ALLOWED);
        }

        $doc = $this->apiDocBuilder->buildMethodDoc($this, $fullMethodName);

        if (!array_filter($doc)) {
            $this->error(sprintf(self::ERROR_NO_API_DOC, $method));
        }

        return $doc;
    }


    /**
     * @param string|NULL $message
     * @param int $code
     * @throws BadRequestException
     */
    public function error($message = NULL, $code = Http\IResponse::S404_NOT_FOUND)
    {
        throw new BadRequestException($message, $code);
    }

    /**
     * @throws Nette\Application\AbortException
     */
    protected function terminate()
    {
        throw new Nette\Application\AbortException();
    }

}
