<?php

namespace App\Repository;

use App\Models\WarehouseCategory;

class WarehouseRepository
{
    /**
     * Returns all warehouse item types as array
     * 
     * @param string $select
     * 
     * @return array
     */
    public function getCategoriesArray(...$select): array
    {
        $items = WarehouseCategory::query()->select(...$select)->get();
        return $items ? $items->toArray() : [];
    }

    public function getAllCategoriesAsConfig()
    {
        $result = [];
        $categories = WarehouseCategory::all();
        if ($categories->count() > 0) {
            foreach ($categories as $item) {
                $name = strtolower(str_ireplace(' ', '', $item->name));
                $result[$name] = $item->id;
            }
        }
        return $result;
    }
}