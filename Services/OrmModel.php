<?php

namespace App\Services;

use Exception;
use App\Services\UbillingOrm;
use Illuminate\Support\Collection;
use ReflectionMethod;

class OrmModel extends Collection
{
    protected $builder;

    protected $extends = [];

    private $errors;

    public function __construct($table = '')
    {
        $this->errors = new Collection();
        $this->initDatabaseBuilder($table);
    }

    private function initDatabaseBuilder($table = '')
    {
        $table = $this->tableName ? $this->tableName : $table;
        $this->builder = clone (new UbillingOrm($table));
        if ($this->get('id')) {
            $this->builder->where('id', '=', $this->get('id'));
        }
    }

    public static function table($name)
    {
        return new static($name);
    }

    protected function extend($key, $value = '')
    {
        $this->extends[$key] = $value;
    }

    /**
     * @param  string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return mixed
     */
    public static function query($key = '', $operator = '', $value = null)
    {
        $instance = new static;
        return $instance::__callStatic(
            'where',
            [$key, $operator, $value, 'self' => $instance]
        );
    }

    /**
     * Get the first item from the collection.
     *
     * @param mixed $param
     * 
     * @return User|null
     */
    public static function getOrNull($param, $column = 'id')
    {
        $instance = new static;
        if ($param) {
            $instance->builder->where($column, '=', $param);
        }
        try {
            $result = $instance->builder->getAll('', false);
            if (!$result) {
                throw new Exception('Database model not found.');
            }
            return self::collectResult($result, $instance)->first();
        } catch (\Exception $e) {
            $instance->errors->push($e->getMessage());
        }
        return $instance;
    }

    /**
     * @param  string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return mixed
     */
    public function where($key, $operator, $value = null)
    {
        return $this->__call('where', [$key, $operator, $value]);
    }

    private static function collectResult($data, $instance = null)
    {
        $collection = new Collection();
        $data = $data ? $data : [];

        foreach ($data as &$each) {
            $inst = !is_null($instance) ? $instance : new static;
            // unset($inst->builder, $inst->tableName);
            foreach ($each as $key => $value) {
                $inst->put($key, $value);
            }
            $each = $inst;
        }
        return $collection::make($data);
    }

    public function error($key = null)
    {
        if (is_null($key)) {
            return $this->errors->first();
        }
        return $this->errors->get($key);
    }

    public function errors()
    {
        return $this->errors;
    }

    public function __call($method, $parameters)
    {
        if (method_exists($this, $method)) {
            if ($this->isNotEmpty()) {
                if ($this->expectArguments($method)) {
                    array_push($parameters, $this->builder, $this);
                }
                return call_user_func_array([$this, $method], $parameters);
            }
        }
        $parameters = array_merge($parameters, ['self' => $this]);
        return self::__callStatic($method, $parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        $instance = new static;
        try {
            if (isset($parameters['self'])) {
                $instance = $parameters['self'];
                unset($parameters['self']);
            }
            if (is_null($instance->builder)) {
                $instance->initDatabaseBuilder();
            }

            if (method_exists($instance, $method)) {
                if ($instance->isNotEmpty()) {
                    return call_user_func_array([$instance, $method], $parameters);
                } elseif (method_exists($instance->builder, $method)) {
                    call_user_func_array([$instance->builder, $method], $parameters);
                    return $instance;
                } else {
                    array_push($parameters, $instance->builder, $instance);
                    call_user_func_array([$instance, $method], $parameters);
                    return $instance;
                }
            }

            if (!method_exists($instance->builder, $method)) {
                throw new \Exception("Method [$method] not exists on instance of " . get_class($instance));
            }
            $result = call_user_func_array([$instance->builder, $method], $parameters);

            if (is_null($result)) return $instance;

            if (is_string($result) || is_integer($result) || is_bool($result)) {
                return $result;
            }
            $collection = self::collectResult($result);
            foreach ($instance->extends as $key => $value) {
                $collection->{$key} = $value;
            }
            return $collection;
        } catch (\Exception $e) {
            $instance->errors->push($e->getMessage());
        }
        return $instance;
    }

    protected function expectArguments($method)
    {
        return !empty((new ReflectionMethod(static::class, $method))->getParameters());
    }

    public function __get($key)
    {
        $result = $this->has($key) ? $this->get($key) : $this->get(ucfirst($key));
        if (!$result && !empty($this->extends)) {
            $result = isset($this->extends[$key]) ? $this->extends[$key] : null;
        }
        return $result;
    }

    public function __set($name, $value)
    {
        if (is_string($value)) {
            $value = strip_tags(mysql_real_escape_string($value));
        }
        $this->put($name, $value);
        if (!is_null($this->builder)) {
            $this->builder->data($name, $value);
        }
    }
}
