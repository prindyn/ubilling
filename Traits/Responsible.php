<?php

namespace App\Traits;

use App\Services\ResponseFactory;
use Symfony\Component\HttpFoundation\Request;

trait Responsible
{
    private $request;
    private $response;

    /**
     * Sends HTTP headers and content.
     * 
     * @param Response|JsonResponse $response
     * @param bool $exit
     * 
     * @return $this
     */
    public function send($response, $exit = true)
    {
        $response->sendHeaders();
        $response->sendContent();

        if (\function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (!\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            $response::closeOutputBuffers(0, true);
        }

        return $exit ? exit : $response;
    }

    /**
     * Return a new response from the application.
     *
     * @param  string $content
     * @param  int  $status
     * @param  array  $headers
     * @return Response
     */
    public function response($content = '', $status = 200, array $headers = [])
    {
        $this->response = new ResponseFactory();

        if (func_num_args() === 0) {
            return $this->response;
        }

        return $this->response->make($content, $status, $headers);
    }

    /**
     * Get an instance of the current request or an input item from the request.
     *
     * @param  array|string|null  $key
     * @param  mixed  $default
     * @return \Symfony\Component\HttpFoundation\Request|string|array|null
     */
    public function request($key = null, $default = null)
    {
        $this->request = !is_null($this->request)
            ? $this->request : Request::createFromGlobals();
        if (is_null($key)) return $this->request;

        if (is_array($key)) return $this->request->only($key);

        $value = $this->request->get($key, $default);

        return is_null($value) ? value($default) : $value;
    }
}
