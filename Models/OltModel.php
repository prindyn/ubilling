<?php

namespace App\Models;

use App\Models\Passive\BaseModel;

class OltModel extends BaseModel
{
    protected $table = 'switchmodels';

    public function pononu()
    {
        return $this->hasMany(Olt::class, 'modelid', 'id');
    }
}
