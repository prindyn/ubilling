<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Models\Passive\BaseModel;

class Employee extends BaseModel
{
    use Filterable;

    protected $table = 'employee';

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admlogin', 'username');
    }
}
