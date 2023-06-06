<?php

namespace App\Models\Passive;

use App\Models\User;
use App\Models\Passive\BaseModel;

class ContractDate extends BaseModel
{
    protected $table = 'contractdates';

    public function user()
    {
        return $this->belongsTo(User::class, 'contract', 'login');
    }
}
