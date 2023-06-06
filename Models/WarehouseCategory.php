<?php

namespace App\Models;

use App\Models\Passive\BaseModel;

class WarehouseCategory extends BaseModel
{
    protected $table = 'wh_categories';

    public function taskTypes()
    {
        return $this->belongsToMany(JobType::class, 'jobtypes_materials', 'materialtype_id', 'jobtype_id');
    }
}
