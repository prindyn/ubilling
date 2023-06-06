<?php

namespace App\Models;

use App\Models\Passive\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class Noi extends BaseModel
{
    protected $table = 'noi';

    const SUBMITTED = 4;

    public function scopeTaskmanExtra(Builder $builder)
    {
        return $builder->select(['id', 'number', 'status'])->get();
    }

    public function config()
    {
        return $this->hasOne(NoiConfig::class, 'noi_id', 'id');
    }

    public static function convertDuration($duration, $format = 'seconds')
    {
        switch ($format) {
            default:
            case 'seconds':
                $duration = strtotime($duration);
                break;
            case 'hours':
                $duration = gmdate("Y-m-d H:i:s", (int) $duration);
                break;
        }
        return $duration;
    }
}
