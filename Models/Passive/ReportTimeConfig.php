<?php

namespace App\Models\Passive;

use App\Services\FileModel;

class ReportTimeConfig extends FileModel
{
    protected $path = 'api/configs';

    protected $table = 'report_time.php';
}
