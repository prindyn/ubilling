<?php

namespace App\Models\Passive;

use App\Models\User;

class AddressExtended extends BaseModel
{
    protected $table = 'address_extended';

    public function user()
    {
        return $this->belongsTo(User::class, 'login', 'login');
    }
}
