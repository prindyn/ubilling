<?php

namespace App\Controllers\Api;

use App\Controllers\AbstractController;
use App\Models\Property;

class PropertyController extends AbstractController
{
    public function getSummary()
    {
        ini_set('memory_limit', '512M');
        $summary = Property::where('active', 1)
            ->where('postcode', '!=', '')
            ->selectRaw('id,uprn,singleline_address,postcode,district,geo,conn_type,active')
            ->get();
        $this->send(
            $this->response()->json($summary->toArray())
        );
    }
}
