<?php

namespace App\Models;

use App\Models\Passive\BaseModel;
use Illuminate\Database\Eloquent\Builder;

class JobType extends BaseModel
{
    protected $table = 'jobtypes';

    public function materialTypes()
    {
        return $this->belongsToMany(WarehouseCategory::class, 'jobtypes_materials', 'jobtype_id', 'materialtype_id');
    }

    public function scopeTaskmanExtra(Builder $builder)
    {
        return $builder->get();
    }
}
