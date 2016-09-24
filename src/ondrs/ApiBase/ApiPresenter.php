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
    const MESSAGE_METHOD_IS_NOT_ALLOWED = 'Method %s is not allowed.';
    const MESSAGE_INVALID_JSON_DATA = 'Invalid JSON data.';
    const MESSAGE_JSON_SCHEMA_ERROR = "JSON does not validate against schema.\n%s";
    const MESSAGE_INVALID_PARAMETER = "Invalid parameter '%s': '%s'.";
    const MESSAGE_MISSING_PARAMETER = "Missing parameter(s) '%s'.";
    const MESSAGE_NO_API_DOC = "No API documentation exists for the method '%s'.";

    /** @var bool */
    public static $mockAllResponses = FALSE;
    protected $mockResponses = FALSE;

    /** @var SchemaValidatorFactory @inject */
    public $schemaValidatorFactory;

    /** @var  SchemaProvider @inject */
    public $schemaProvider;

    /** @var FakeResponse @inject */
    public $fakeResponse;


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
            $data = $this->fakeResponse->generate($this->getSchemaFile('response', $action));

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
     * @return string
     */
    public function getSchemaFile($what, $action)
    {
        $dir = dirname((new ReflectionClass(static::class))->getFileName());

        return "$dir/" . lcfirst($action) . ".$what.neon";
    }


    /**
     * @param string $what
     * @param string $action
     * @param \stdClass $data
     * @throws BadRequestException
     */
    public function validate($what, $action, $data)
    {
        $schemaFile = $this->getSchemaFile($what, $action);

        if (!file_exists($schemaFile)) {
            return;
        }

        $validator = $this->schemaValidatorFactory->create($schemaFile);

        if ($validator->isValid($data) === FALSE) {
            $errors = Json::encode($validator->getErrors(), Json::PRETTY);
            $this->error(sprintf(self::MESSAGE_JSON_SCHEMA_ERROR, $errors), Http\IResponse::S400_BAD_REQUEST);
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
            $this->error(sprintf(self::MESSAGE_MISSING_PARAMETER, join(', ', $missingParams)), Http\IResponse::S400_BAD_REQUEST);
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
            $this->error(sprintf(self::MESSAGE_METHOD_IS_NOT_ALLOWED, strtoupper($request->method)), Http\IResponse::S405_METHOD_NOT_ALLOWED);
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
            $this->error(self::MESSAGE_INVALID_JSON_DATA, Http\IResponse::S400_BAD_REQUEST);
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

        if ($reflection->hasMethod($fullMethodName)) {
            $reflection = $reflection->getMethod($fullMethodName);
        } else {
            $this->error(sprintf(self::MESSAGE_METHOD_IS_NOT_ALLOWED, strtoupper($method)), Http\IResponse::S405_METHOD_NOT_ALLOWED);
        }

        $data = [
            'request' => $this->getSchemaFile('request', $method),
            'response' => $this->getSchemaFile('response', $method),
        ];

        foreach ($data as $key => $schemaFile) {
            $data[$key] = file_exists($schemaFile)
                ? [
                    'schema' => $this->schemaProvider->get($schemaFile),
                    'example' => $this->fakeResponse->generate($schemaFile),
                ]
                : NULL;
        }

        $data['description'] = $reflection->getDescription();
        $data['url'] = $reflection->getAnnotation('url');

        $res = $reflection->getAnnotations();
        $data['parameters'] = isset($res['param']) ? $res['param'] : NULL;

        if (!array_filter($data)) {
            $this->error(sprintf(self::MESSAGE_NO_API_DOC, $method));
        }

        return $data;
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
