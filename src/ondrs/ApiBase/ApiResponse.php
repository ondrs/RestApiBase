<?php

namespace ondrs\ApiBase;

use Nette\Http\IResponse;
use Nette\Http\IRequest;
use Nette\Application\Responses\JsonResponse;

class ApiResponse extends JsonResponse
{

    /** @var int */
    private $responseCode;


    /**
     * @param array|\stdClass $payload
     * @param null $responseCode
     */
    public function __construct($payload, $responseCode)
    {
        parent::__construct($payload);

        if (is_string($responseCode)) {
            $responseCode = (int)substr($responseCode, 0, 3);
        }

        $this->responseCode = $responseCode;
    }

    /**
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }


    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->responseCode >= 200 && $this->responseCode <= 300;
    }


    /**
     * @param IRequest $httpRequest
     * @param IResponse $httpResponse
     */
    public function send(IRequest $httpRequest, IResponse $httpResponse)
    {
        $httpResponse->setCode($this->responseCode);

        parent::send($httpRequest, $httpResponse);
    }

}
