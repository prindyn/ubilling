<?php

namespace App\Models;

use App\Models\Passive\BaseModel;

class PonOnu extends BaseModel
{
    protected $table = 'pononu';

    public function olt()
    {
        return $this->belongsTo(Olt::class, 'oltid', 'id');
    }
}
