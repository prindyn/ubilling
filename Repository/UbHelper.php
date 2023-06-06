<?php

namespace App\Repository;

class UbHelper
{
    protected static function ajaxUnknownRequest()
    {
        return self::ajaxResponse(['status' => 200, 'message' => 'Unknown action']);
    }

    protected static function ajaxResponse($data)
    {
        die(json_encode($data));
    }
}
