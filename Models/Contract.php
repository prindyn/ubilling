<?php

namespace App\Models;

use App\Models\User;
use App\Models\Passive\BaseModel;
use Illuminate\Database\Eloquent\Builder;

class Contract extends BaseModel
{
    public function user()
    {
        return $this->belongsTo(User::class, 'login', 'login');
    }

    public function scopeTaskmanExtra(Builder $builder)
    {
        return $builder->select(['id', 'login', 'contract'])->get();
    }
}
