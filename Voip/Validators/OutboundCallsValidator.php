<?php

namespace App\Voip\Validators;

use App\Voip\Interfaces\ValidatorInterface;
use ubRouting;

class OutboundCallsValidator implements ValidatorInterface
{
    const FILE_HEADER_TPL = ['CallType', 'Time', 'Extension', 'Source', 'Destination', 'Duration', 'Seconds', 'Cost'];

    const DB_COLUMNS = ['calltype', 'date', 'time', 'extension', 'source', 'destination', 'duration', 'seconds', 'cost'];

    public static function validate($data)
    {
        if (empty($data[0]) || explode(',', $data[0]) != self::FILE_HEADER_TPL) return false;

        foreach ($data as $io => &$eachRow) {

            $rowArr = explode(',', mysql_real_escape_string(strip_tags($eachRow)));

            if ($io == 0 || empty($eachRow) || count($rowArr) != count(self::FILE_HEADER_TPL)) {

                unset($data[$io]);

                continue;
            }
            array_splice($rowArr, 1, 0, [date('Y-m-d')]);

            $eachRow = array_combine(self::DB_COLUMNS, $rowArr);
        }
        return $data;
    }
}
