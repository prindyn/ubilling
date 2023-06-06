<?php

namespace App\Traits;

trait Searchable
{
    // protected static function searchWhere($search, $rules = [])
    // {
    //     $result = '';
    //     $search = $search;
    //     $rules = $rules ? $rules : static::searchRules();

    //     if (!empty($search) && $rules) {
    //         $result = 'WHERE ' . implode(' ', $rules);
    //         $result = str_ireplace(['{}', '{search}'], $search, $result);
    //     }

    //     return $result;
    // }

    // protected static function total($table, $search = '', $rules = [])
    // {
    //     if (is_array($search) || is_object($search)) return count($search);

    //     $search = self::searchWhere($search, $rules);

    //     try {
    //         $query = "SELECT COUNT(*) AS `total` FROM `$table` $search";
    //         return simple_query($query)['total'];
    //     } catch (\Exception $e) {
    //         return 0;
    //     }
    // }

    public static function search($search)
    {
        if (is_null($search)) return null;
        $instance = new static;
        $rules = $instance->rules();
        return str_ireplace('{}', $search, implode(' ', $rules));
    }

    /**
     * Contains array of search rules
     * Rule example: `field` [=, >, <, LIKE...] '%[{}, {search}]%'
     */
    abstract protected function rules();
}
