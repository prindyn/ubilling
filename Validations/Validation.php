<?php

namespace App\Validations;

use App\Traits\Responsible;

abstract class Validation
{
    use Responsible;

    protected $data = [];

    protected $rules = [];

    protected $messages = [];

    protected $attributes = [];

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    public function validate($scopes, $validator)
    {
        $scopes = is_array($scopes) ? $scopes : [$scopes];

        foreach ($scopes as $scope) {
            $rules = method_exists($this, "{$scope}Rules") ? $this->{"{$scope}Rules"}() : [];
            $messages = method_exists($this, "{$scope}Messages") ? $this->{"{$scope}Messages"}() : [];
            $attributes = method_exists($this, "{$scope}Attributes") ? $this->{"{$scope}Attributes"}() : [];

            if (method_exists($this, $scope)) {
                $this->$scope();
            } else {
                $this->rules = array_merge($this->rules, $rules);
                $this->messages = array_merge($this->messages, $messages);
                $this->attributes = array_merge($this->attributes, $attributes);
            }
        }

        return $validator->make($this->data, $this->rules, $this->messages, $this->attributes);
    }
}
