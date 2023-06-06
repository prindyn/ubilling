<?php

namespace App\Voip;

use App\Voip\SipProvider;

class Gradwell extends SipProvider
{
    public function get()
    {
        return $this->response()['body'];
    }

    public function asArray()
    {
        return explode(PHP_EOL, $this->get());
    }

    public function endpoint($endpoint)
    {
        parent::endpoint($endpoint);

        $this->endpoint = $this->finalUrl("billing/call/$this->endpoint");

        return $this;
    }

    protected function setConnection()
    {
        $this->connection = [
            'options' => [
                'dataPost' => [
                    'email_address' => $this->configs['USERNAME'],
                    'password' => $this->configs['PASSWORD'],
                ]
            ],
        ];
    }

    protected function finalUrl($endpoint = '')
    {
        $endpoint = (isset($this->configs['URL']) ? $this->configs['URL'] : '') . $endpoint;

        return "https://sso.prod.gradwell.com/login?url={$endpoint}";
    }

    protected function setConfigPath()
    {
        $this->configPath = 'config/gradwell.ini';
    }
}
