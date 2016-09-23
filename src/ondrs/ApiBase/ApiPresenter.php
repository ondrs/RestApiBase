<?php

namespace ondrs\ApiBase;

use Nette;
use Nette\Application\BadRequestException;
use Nette\Application\LinkGenerator;
use Nette\Http;
use Nette\Application\Request;
use DateTime;
use Nette\Security\User;
use Nette\Utils\Json;
use Nette\Utils\JsonException;


abstract class ApiPresenter implements Nette\Application\IPresenter
{
    const MESSAGE_METHOD_IS_NOT_ALLOWED = 'Method %s is not allowed.';
    const MESSAGE_INVALID_JSON_DATA = 'Invalid JSON data.';
    const MESSAGE_INVALID_PARAMETER = "Invalid parameter '%s': '%s'.";
    const MESSAGE_MISSING_PARAMETER = "Missing parameter(s) '%s'.";

    /** @var User */
    protected $user;

    /** @var  LinkGenerator */
    protected $linkGenerator;

    /** @var Http\Request */
    protected $httpRequest;

    /** @var Http\Response */
    protected $httpResponse;


    /** @var Request */
    protected $request;

    /** @var string */
    protected $rawBody;

    /** @var \stdClass|NULL */
    protected $body;


    public function __construct(
        User $user,
        LinkGenerator $linkGenerator,
        Http\IRequest $httpRequest,
        Http\Response $httpResponse)
    {
        $this->user = $user;
        $this->linkGenerator = $linkGenerator;
        $this->httpRequest = $httpRequest;
        $this->httpResponse = $httpResponse;
    }


    protected function startup()
    {
        // pass
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
        }

        $data = $this->dispatch($request, $action);

        if (!$data) {
            $data = [];
        }

        return new ApiResponse(self::filterData($data), Http\IResponse::S200_OK);
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
