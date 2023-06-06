<?php

namespace App\Traits;

use App\Services\ResponseFactory;
use Symfony\Component\HttpFoundation\Request;

trait Authenticable
{
    private $authRequest;

    /** 
     * Get header Authorization
     * */
    protected function getAuthorizationHeader()
    {
        $server = $this->authRequest()->server;
        $headers = $this->authRequest()->headers;
        if ($server->get('Authorization')) {
            $headers = trim($server->get("Authorization"));
        } else if ($server->get('HTTP_AUTHORIZATION')) { //Nginx or fast CGI
            $headers = trim($server->get("HTTP_AUTHORIZATION"));
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * get access token from header
     * */
    protected function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Get an instance of the current request or an input item from the request.
     *
     * @param  array|string|null  $key
     * @param  mixed  $default
     * @return \Symfony\Component\HttpFoundation\Request|string|array|null
     */
    private function authRequest($key = null, $default = null)
    {
        $this->authRequest = !is_null($this->authRequest)
            ? $this->authRequest : Request::createFromGlobals();
        if (is_null($key)) return $this->authRequest;

        if (is_array($key)) return $this->authRequest->only($key);

        $value = $this->authRequest->get($key, $default);

        return is_null($value) ? value($default) : $value;
    }
}
