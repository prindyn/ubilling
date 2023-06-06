<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoiConfig extends Model
{
    protected $table = "noi_configs";

    protected $guarded = ['gst_nick'];

    public function configs()
    {
        return $this->belongsTo(Noi::class, 'noi_id', 'id');
    }
}
