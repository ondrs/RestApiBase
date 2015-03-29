<?php

namespace Clevis\RestApi;

use Nette;
use Nette\Http;
use Nette\Application\Request;
use Nette\Reflection\ClassType;
use Nette\DI\Container;

use DateTime;
use Nette\Security\IAuthenticator;
use Nette\Security\User;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tracy\Debugger;


/**
 * API base presenter
 *
 * lifecycle:
 * - calls `startup()`
 * - ssl validation
 * - api version validation
 * - data decoding and schema validation
 * - access validation
 * - calls `actionXyz()`
 * - returns response
 */
abstract class ApiPresenter implements Nette\Application\IPresenter
{

    const MESSAGE_SSL_IS_REQUIRED = 'SSL is required.';
    const MESSAGE_MINIMAL_SUPPORTED_API_VERSION = 'Minimal supported API version is %s.';
    const MESSAGE_MAXIMAL_SUPPORTED_API_VERSION = 'Maximal supported API version is %s.';
    const MESSAGE_AUTHORIZATION_FAILED = 'Authorization failed.';
    const MESSAGE_METHOD_IS_NOT_ALLOWED = 'Method %s is not allowed.';
    const MESSAGE_INVALID_JSON_DATA = 'Invalid JSON data.';
    const MESSAGE_INVALID_PARAMETER = 'Invalid parameter `%s`: \'%s\'.';
    const MESSAGE_MISSING_PARAMETER = 'Missing parameter `%s`.';

    /** @var Container */
    protected $context;

    /** @var IAuthenticator */
    protected $authenticator;

    /** @var Http\Context */
    protected $httpContext;

    /** @var Http\Request */
    protected $httpRequest;

    /** @var Http\Response */
    protected $httpResponse;


    /** @var bool */
    protected $checkSsl = TRUE;

    /** @var bool */
    protected $checkAccess = TRUE;

    /** @var int|NULL */
    protected $minApiVersion = NULL;

    /** @var int|NULL */
    protected $maxApiVersion = NULL;


    /** @var Request */
    protected $request;

    /** @var ApiResponse */
    protected $response;

    /** @var string nedekódovaná POST data */
    protected $rawPostData;

    /** @var array data požadavku dekódovaná z JSONu */
    protected $data;

    /** @var array vracená data */
    protected $payload = array();

    /** @var User */
    protected $user;


    final public function injectPrimary(
        Container $context,
        User $user,
        IAuthenticator $authenticator,
        Http\Context $httpContext,
        Http\IRequest $httpRequest,
        Http\Response $httpResponse)
    {
        if ($this->context !== NULL) {
            throw new Nette\InvalidStateException("Method " . __METHOD__ . " is intended for initialization and should not be called more than once.");
        }

        $this->context = $context;
        $this->user = $user;
        $this->authenticator = $authenticator;
        $this->httpContext = $httpContext;
        $this->httpRequest = $httpRequest;
        $this->httpResponse = $httpResponse;
    }

    protected function startup()
    {
        // pass
    }

    public function run(Request $request)
    {
        $this->request = $request;

        try {
            $this->startup();

            $this->response = NULL;

            if ($this->checkSsl) {
                $this->checkSsl();
            }

            $this->checkApiVersion();

            $name = $this->request->presenterName;
            $action = isset($this->request->parameters['action']) ? $this->request->parameters['action'] : 'default';

            // kontrola dat
            if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch'))
            {
                $this->getRawPostData();
                $this->prepareData($name, $action);
            }

            // kontrola přístupu
            if ($this->checkAccess) {
                $this->checkAccess($action);
            }

            $this->despatch($request, $action);

            if (!$this->response) {
                $this->response = new ApiResponse($this->payload, ApiResponse::S200_OK);
            }
        } catch (Nette\Application\AbortException $e) {
            // pass
        } catch (\Exception $e) {
            Debugger::log($e);
            $this->response = NULL;
        }

        if (!$this->response) {
            $this->response = new ApiResponse(array(), ApiResponse::S500_INTERNAL_SERVER_ERROR);
        }

        return $this->response;
    }

    /**
     * Kontroluje SSL
     */
    protected function checkSsl()
    {
        if (!$this->request->hasFlag(Request::SECURED)) {
            $this->sendErrorResponse(ApiResponse::S403_FORBIDDEN, static::MESSAGE_SSL_IS_REQUIRED);
        }
    }

    /**
     * Kontroluje verzi API
     */
    protected function checkApiVersion()
    {
        if (isset($this->minApiVersion) && $this->getHeader('X-Api-Version') < $this->minApiVersion) {
            $this->sendErrorResponse(ApiResponse::S426_UPGRADE_REQUIRED, sprintf(static::MESSAGE_MINIMAL_SUPPORTED_API_VERSION, $this->minApiVersion));
        }
        if (isset($this->maxApiVersion) && $this->getHeader('X-Api-Version') > $this->maxApiVersion) {
            $this->sendErrorResponse(ApiResponse::S426_UPGRADE_REQUIRED, sprintf(static::MESSAGE_MAXIMAL_SUPPORTED_API_VERSION, $this->maxApiVersion));
        }
    }

    /**
     * Kontroluje přístup
     *
     * @param string
     */
    protected function checkAccess($action)
    {
        if($apiKey = $this->getHeader('X-Api-Key')) {
            $this->authenticator->authenticate([$apiKey]);
        }

        if (!$this->user->isLoggedIn()) {
            $this->sendErrorResponse(ApiResponse::S401_UNAUTHORIZED, static::MESSAGE_AUTHORIZATION_FAILED);
        }
    }

    /**
     * Volá příslušnou akci presenteru
     *
     * @param Request
     * @param string
     */
    protected function despatch(Request $request, $action)
    {
        $method = 'action' . $action;
        if (!method_exists($this, $method)) {
            $this->sendErrorResponse(ApiResponse::S405_METHOD_NOT_ALLOWED, sprintf(static::MESSAGE_METHOD_IS_NOT_ALLOWED, strtoupper($request->method)));
        }

        $params = $request->parameters;
        unset($params['action']);

        call_user_func_array(array($this, $method), $params);
    }

    /**
     * Získává data požadavku
     */
    protected function getRawPostData()
    {
        $this->rawPostData = file_get_contents('php://input');
    }

    /**
     * Parsuje a validuje data rekvestu
     *
     * @param string
     */
    protected function prepareData()
    {
        $this->data =  $this->parseData($this->rawPostData);
    }

    /**
     * Parsuje POST data
     * Překryjte pro jiný formát dat (např. XML)
     *
     * @param string
     * @return array
     */
    protected function parseData($data)
    {
        try {
            return Json::decode($data, Json::FORCE_ARRAY);
        } catch (JsonException $e) {
            $this->sendErrorResponse(ApiResponse::S400_BAD_REQUEST, static::MESSAGE_INVALID_JSON_DATA);
            exit; // IDE shut up
        }
    }


    /**
     * Kontrola povinného parametru
     *
     * @param mixed
     * @param string
     */
    protected function checkParamRequired($value, $name)
    {
        if (!$value) {
            $this->sendErrorResponse(ApiResponse::S400_BAD_REQUEST, sprintf(static::MESSAGE_MISSING_PARAMETER, $name));
        }
    }

    /**
     * Kontrola parametru
     *
     * @param mixed
     * @param string
     * @param string
     */
    protected function checkParamValid($condition, $param, $reason = '')
    {
        if (!$condition) {
            $this->sendErrorResponse(ApiResponse::S400_BAD_REQUEST, sprintf(static::MESSAGE_INVALID_PARAMETER, $param, $reason));
        }
    }

    /**
     * Odešle odpověď a ukončí aplikaci
     *
     * @param array|NULL
     * @param int|string|NULL
     */
    protected function sendSuccessResponse($data = NULL, $responseCode = ApiResponse::S200_OK)
    {
        if ($data !== NULL) {
            $this->payload = $data;
        }

        $this->filterData($this->payload);

        $this->sendResponse(new ApiResponse($this->payload, $responseCode));
    }

    /**
     * Odstraňuje z dat klíče s hodnotou NULL, formátuje DateTime
     */
    protected function filterData(&$data)
    {
        foreach ($data as $key => &$value) {
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d\\TH:i:sP');
            } elseif (is_array($value)) {
                $this->filterData($value);
            }
        }
    }

    /**
     * Odešle zprávu s chybovým kódem a ukončí aplikaci
     *
     * @param int $errorCode chybový kod API
     * @param array|string pole chyb nebo jeden či více parametrů ke zformátování chybové zprávy
     */
    protected function sendErrorResponse($errorCode, $message = '')
    {
        $this->sendResponse(new ApiResponse(array('message' => $message), $errorCode));
    }

    /**
     * Odešle odpověď a ukončí presenter
     *
     * @param ApiResponse
     */
    protected function sendResponse(ApiResponse $response)
    {
        @header_remove('x-powered-by');
        $this->response = $response;
        $this->terminate();
    }

    /**
     * Ukončuje presenter
     *
     * @throws Nette\Application\AbortException
     */
    protected function terminate()
    {
        throw new Nette\Application\AbortException();
    }


    /**
     * Helper
     *
     * @param string
     * @return string|NULL
     */
    private function getHeader($name)
    {
        return $this->httpRequest->getHeader($name);
    }

}
