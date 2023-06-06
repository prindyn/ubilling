<?php

namespace App\Filters;

class OnlineFilter extends QueryFilter
{
    public function tag(int $tag)
    {
        $this->builder->whereHas('tags', function ($q) use ($tag) {
            $q->where('tagid', $tag);
        });
    }

    public function dcmsStatus(int $status)
    {
        $this->builder->whereHas('dcmsFunding', function ($q) use ($status) {
            $q->where('status', $status);
        });
    }
}
