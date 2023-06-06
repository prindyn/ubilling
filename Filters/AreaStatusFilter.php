<?php

namespace App\Filters;

class AreaStatusFilter extends QueryFilter
{
    /**
     * @param string $search
     */
    public function search(string $search)
    {
        $this->builder->where(function ($query) use ($search) {
            $query->orWhere('date', 'like', "%$search%")
                ->orWhere('description', 'like', "%$search%")
                ->orWhere('postcodes', 'like', "%$search%")
                ->orWhere('admin', 'like', "%$search%");
        });
    }
}
