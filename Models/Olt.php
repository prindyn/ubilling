<?php

namespace App\Models;

use App\Models\Passive\BaseModel;

class Olt extends BaseModel
{
    protected $table = 'switches';

    public function pononu()
    {
        return $this->hasMany(PonOnu::class, 'oltid', 'id');
    }

    public function model()
    {
        return $this->hasOne(OltModel::class, 'id', 'modelid');
    }
}
