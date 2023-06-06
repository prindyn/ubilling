<?php

namespace App\Models;

use App\Models\Passive\BaseModel;

class Speed extends BaseModel
{
    protected $table = 'speeds';

    public function tariff()
    {
        return $this->belongsTo(Tariff::class, 'name', 'tariff');
    }
}
