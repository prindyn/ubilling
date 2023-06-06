<?php

namespace App\Repository;

trait PropertyContainer
{
    protected $container = [];

    protected function get($name)
    {
        return isset($this->container[$name]) ? $this->container[$name] : null;
    }

    protected function set($name, $value)
    {
        $this->container[$name] = $value;
    }

    protected function exists($name)
    {
        return isset($this->container[$name]);
    }
}