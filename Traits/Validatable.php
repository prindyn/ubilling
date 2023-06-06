<?php

namespace App\Traits;

use App\Services\ValidatorFactory;
use App\Validations\Validation;
use Illuminate\Support\Collection;

trait Validatable
{
    private $validator;

    private $validated;

    /**
     * Create a new Validator instance.
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return \Illuminate\Validation\Validator
     */
    public function validator($data = [], $rules = [], $messages = [], $customAttributes = [])
    {
        $this->validator = new ValidatorFactory;

        if (func_num_args() === 0) {
            return $this->validator;
        }
        $validator = $this->validator->make($data, $rules, $messages, $customAttributes);
        $this->validated = collect($validator->valid());

        return $validator;
    }

    /**
     * @return \Illuminate\Validation\Validator
     */
    public function validation(Validation $validation, $scopes)
    {
        $validator = $validation->validate($scopes, $this->validator());
        $this->validated = collect($validator->valid());

        return $validator;
    }

    public function validated($key = '', $default = null)
    {
        if ($this->validated instanceof Collection) {
            if ($key) {
                $item = $this->validated->get($key, $default);
                return is_array($item) ? $item : mysql_real_escape_string(strip_tags($item));
            }

            return $this->validated->transform(function ($item) {
                return is_array($item) ? $item : mysql_real_escape_string(strip_tags($item));
            })->all();
        }

        return null;
    }

    public function setValidated($key, $value = null)
    {
        if ($this->validated instanceof Collection) {
            is_array($key)
                ? $this->validated = $this->validated->merge($key)
                : $this->validated->put($key, $value);
            return true;
        }

        return false;
    }
}
