<?php
/*
 * ClickSend
 *
 * This file was automatically generated for ClickSend by APIMATIC v2.0 ( https://apimatic.io ).
 */

namespace ClickSendLib\Controllers;

use ClickSendLib\Http\HttpCallBack;
use ClickSendLib\Http\HttpContext;
use ClickSendLib\Http\HttpResponse;
use ClickSendLib\APIException;
use ClickSendLib\Exceptions;
use \apimatic\jsonmapper\JsonMapper;
use Unirest\Request;

/**
* Base controller
*/
class BaseController
{
    /**
     * HttpCallBack instance associated with this controller
     * @var HttpCallBack
     */
    private $httpCallBack = null;

     /**
     * Constructor that sets the timeout of requests
     */

    /**
     * Set HttpCallBack for this controller
     * @param HttpCallBack $httpCallBack Http Callbacks called before/after each API call
     */
    public function setHttpCallBack(HttpCallBack $httpCallBack)
    {
        $this->httpCallBack = $httpCallBack;
    }

    /**
     * Get HttpCallBack for this controller
     * @return HttpCallBack The HttpCallBack object set for this controller
     */
    public function getHttpCallBack()
    {
        return $this->httpCallBack;
    }

    /**
     * Get a new JsonMapper instance for mapping objects
     * @return \apimatic\jsonmapper\JsonMapper JsonMapper instance
     */
    protected function getJsonMapper()
    {
        $mapper = new JsonMapper();
        return $mapper;
    }

    protected function validateResponse(HttpResponse $response, HttpContext $_httpContext)
    {
        if ($response->getStatusCode() == 400) {
            throw new APIException('BAD_REQUEST', $_httpContext);
        }

        if ($response->getStatusCode() == 401) {
            throw new APIException('UNAUTHORIZED', $_httpContext);
        }

        if ($response->getStatusCode() == 403) {
            throw new APIException('FORBIDDEN', $_httpContext);
        }

        if ($response->getStatusCode() == 404) {
            throw new APIException('NOT_FOUND', $_httpContext);
        }

        if ($response->getStatusCode() == 405) {
            throw new APIException('METHOD_NOT_FOUND', $_httpContext);
        }

        if ($response->getStatusCode() == 429) {
            throw new APIException('TOO_MANY_REQUESTS', $_httpContext);
        }

        if ($response->getStatusCode() == 500) {
            throw new APIException('INTERNAL_SERVER_ERROR', $_httpContext);
        }

        if (($response->getStatusCode() < 200) || ($response->getStatusCode() > 208)) { //[200,208] = HTTP OK
            throw new APIException('HTTP Response Not OK', $_httpContext);
        }
    }
}
