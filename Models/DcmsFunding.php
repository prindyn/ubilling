<?php

namespace App\Models;

use App\Models\Passive\BaseModel;

class DcmsFunding extends BaseModel
{
    protected $table = 'users_dcms_fundings';

    public function statuses()
    {
        return $this->belongsTo(User::class, 'login', 'login');
    }
}
