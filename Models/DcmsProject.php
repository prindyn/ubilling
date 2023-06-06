<?php

namespace App\Models;

use App\Models\Passive\BaseModel;
use Illuminate\Database\Eloquent\Builder;

class DcmsProject extends BaseModel
{
    protected $table = 'dcms_fundings';

    public function statuses()
    {
        return $this->hasMany(AreaStatus::class, 'project_id', 'id');
    }

    public function scopeTaskmanExtra(Builder $builder)
    {
        return $builder->select(['id', 'name'])->get();
    }

    public static function getNameById($id)
    {
        $project = self::find($id);

        return $project ? $project->name : null;
    }
}
