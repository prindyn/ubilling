<?php

namespace App\Models;

use App\Models\Passive\BaseModel;

class WarehouseItemType extends BaseModel
{
    protected $table = 'wh_itemtypes';

    public function taskTypes()
    {
        return $this->belongsToMany(JobType::class, 'jobtypes_materials', 'materialtype_id', 'jobtype_id');
    }
}
