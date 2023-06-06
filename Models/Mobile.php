<?php

namespace App\Models;

use App\Models\User;
use App\Models\Passive\BaseModel;

class Mobile extends BaseModel
{
    public function user()
    {
        return $this->belongsTo(User::class, 'login', 'login');
    }
}
