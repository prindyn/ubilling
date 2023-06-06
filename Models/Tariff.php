<?php

namespace App\Models;

use App\Models\User;
use App\Models\Passive\BaseModel;

class Tariff extends BaseModel
{
    protected $table = 'tariffs';

    public function user()
    {
        return $this->belongsTo(User::class, 'name', 'Tariff');
    }

    public function speed()
    {
        return $this->hasOne(Speed::class, 'tariff', 'name');
    }
}
