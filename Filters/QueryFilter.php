<?php

namespace App\Filters;

use Illuminate\Support\Str;
use App\Traits\Responsible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

abstract class QueryFilter
{
    use Responsible;

    /**
     * @var Collection|Builder
     */
    protected $builder;

    /**
     * @param Builder $builder
     */
    public function apply(Builder $builder)
    {
        $this->builder = $builder;
        $fields = $this->fields();
        
        if (property_exists($this, 'staticFilters')) {
            $fields = array_merge($fields, $this->staticFilters);
        } 

        foreach ($fields as $field => $value) {
            $method = Str::camel($field);
            if (method_exists($this, $method)) {
                call_user_func_array([$this, $method], (array)$value);
            }
        }
    }

    /**
     * @param Collection $collection
     * @param array $fields
     * 
     * @return Collection
     */
    public static function filter(Collection $collection, $fields = [])
    {
        $instance = new static;
        $instance->builder = $collection;
        $fields = $fields ? $fields : $instance->fields();

        foreach ($fields as $field => $value) {
            $method = Str::camel($field);
            if (method_exists($instance, $method)) {
                $instance->builder = call_user_func_array([$instance, $method], (array)$value);
            }
        }

        return $instance->builder->values();
    }

    /**
     * @return array
     */
    protected function fields(): array
    {
        return array_map('trim', $this->request()->query->all());
    }
}
