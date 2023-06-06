<?php

namespace App\Models;

use App\Models\User;
use App\Models\Passive\BaseModel;

class GcssMandate extends BaseModel
{
    protected $table = 'gcss_mandates';

    public function user()
    {
        return $this->belongsTo(User::class, 'login', 'login');
    }
}
