<?php

namespace App\Models;

use App\Models\Passive\BaseModel;

class StartDate extends BaseModel
{
    protected $table = 'traffic_start_dates';

    public function user()
    {
        return $this->belongsTo(User::class, 'login', 'login');
    }
}
