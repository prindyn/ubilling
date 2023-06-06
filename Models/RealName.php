<?php

namespace App\Models;

use App\Models\User;
use App\Models\Passive\BaseModel;

class RealName extends BaseModel
{
    protected $table = 'realname';

    public function user()
    {
        return $this->belongsTo(User::class, 'login', 'login');
    }
}
