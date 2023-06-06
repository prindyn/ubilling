<?php

namespace App\Models;

use App\Models\User;
use App\Models\Passive\BaseModel;

class Nethost extends BaseModel
{
    public function user()
    {
        return $this->belongsTo(User::class, 'IP', 'ip');
    }
}
