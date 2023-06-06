<?php

namespace App\Models;

use App\Models\Passive\BaseModel;
use App\Services\ValidatorFactory;

class PhotoStorage extends BaseModel
{
    protected $table = 'photostorage';

    public static function validateItems($items)
    {
        $errors = collect();
        $validator = new ValidatorFactory;

        foreach ($items as $item) {
            if (empty($item)) continue;
            $params = ['id' => trim($item)];
            $validated = $validator->make($params, [
                'id' => 'integer|required|exists:photostorage,id'
            ]);

            if (count($validated->errors()) > 0) {
                $errors->push($validated->errors()->first());
            }
        }

        return $errors;
    }
}
