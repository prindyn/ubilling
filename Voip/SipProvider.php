<?php

namespace App\Voip;

use OmaeUrl;

abstract class SipProvider
{
    protected $connection;

    protected $endpoint;

    protected $response;

    protected $configPath;

    protected $configs = [];

    public function __construct()
    {
        $this->loadConfigs();

        $this->login();
    }

    public function endpoint($endpoint)
    {
        $endpoint = is_array($endpoint) ? implode('/', $endpoint) : $endpoint;
        
        $this->endpoint = $endpoint;
    }

    protected function login()
    {
        if (!$this->connection) $this->setConnection();
    }

    protected function loadConfigs()
    {
        $this->setConfigPath();

        $this->configs = parse_ini_file($this->configPath);
    }

    protected function response($payload = [])
    {
        $payload = $payload ? $payload : $this->connection;

        $request = new OmaeUrl($this->endpoint);

        $request->setTimeout(isset($payload['timeout']) ? $payload['timeout'] : 2);

        $request->setHeadersReturn(isset($payload['header_return']));

        if (!empty($payload['options'])) {

            foreach ($payload['options'] as $method => $eachData) {

                if (!empty($eachData)) {

                    foreach ($eachData as $key => $eachValue) {

                        $request->$method($key, $eachValue);
                    }
                }
            }
        }
        $response['body'] = $request->response();

        $response['headers'] = $request->getResponseHeaders();

        return $response;
    }

    public abstract function get();

    protected abstract function setConfigPath();

    protected abstract function setConnection();
}
