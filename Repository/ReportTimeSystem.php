<?php

namespace App\Repository;

use App\Models\VehicleCheck;

class ReportTimeSystem
{
    const ITEMS = ['checks', 'statuses'];

    protected static $only = null;

    protected static $collection = null;

    public static function only($items)
    {
        $instance = new static;

        if (!empty($items)) {
            $instance::$only = is_array($items) ? $items : collect(
                array_map('trim', explode(',', strtolower($items)))
            );
        }

        return $instance;
    }

    public static function get()
    {
        return collect(self::collection());
    }

    protected static function collection()
    {
        $result = [];
        $instance = new static;
        $relations = $instance->itemsRelations();

        foreach (self::ITEMS as $item) {

            if ((!self::$only || self::$only->search($item) !== false) && isset($relations[$item])) {
                $rule = $relations[$item];

                if (is_array($rule)) {
                    if (!empty($rule['prop'])) {
                        if (property_exists($rule['class'], $rule['prop'])) {
                            $result[$item] = (new $rule['class'])->{$rule['prop']};
                        }
                    } else if (!empty($rule['func'])) {
                        if (method_exists($rule['class'], $rule['func'])) {
                            $result[$item] = (new $rule['class'])->{$rule['func']}();
                        }
                    }
                } else if (method_exists($rule, 'scopeTaskmanSystem')) {
                    $result[$item] = (new $rule)->scopeTaskmanExtra($rule::query());
                }
            }
        }

        return $result;
    }

    protected function itemsRelations()
    {
        return [
            'checks' => [
                'class' => VehicleCheck::class,
                'func' => 'checks',
            ],
            'statuses' => [
                'class' => ReportTime::class,
                'func' => 'statuses',
            ],
        ];
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this, $name)) {
            return call_user_func_array($this->$name(), $arguments);
        }
    }
}
