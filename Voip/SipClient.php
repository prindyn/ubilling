<?php

namespace App\Voip;

use Exception;
use App\Repository\PropertyContainer;

class SipClient
{
    use PropertyContainer;

    const CALL_DIR_CODES = [
        '07' => 0,
        '01' => 1,
        '02' => 1,
        '03' => 1,
        '00' => 2,
    ];

    const CALL_DIR_TYPES = [
        0 => 'Mobile',
        1 => 'Landline',
        2 => 'International',
    ];

    public function save($data, $table, $replaces = [])
    {
        $validatedData = $this->validate($data, $table);

        if (!$validatedData) {

            echo ("Received data are not valid or empty." . PHP_EOL);

            return;
        }

        $orm = "nya_{$table}";

        foreach ($validatedData as $io => $eachData) {

            $orm = new $orm;

            foreach ($eachData as $key => $eachValue) {

                if (!empty($replaces[$key])) $eachValue = mysql_real_escape_string(strip_tags($replaces[$key]));

                $orm->data($key, $eachValue);
            }
            $orm->create();
        }
    }

    public function from($provider)
    {
        if (!$this->exists($provider)) {

            throw new Exception("The SIP provider not found or unregistered.");
        }

        return $this->get($provider);
    }

    public function registerProvider($provider, $name = '')
    {
        $name = $name ? $name : get_class($provider);

        $this->set($name, $provider);
    }

    public static function callDirectionType($destNumber, $typeId = false)
    {
        $numberPref = substr($destNumber, 0, 2);

        $directionId = @self::CALL_DIR_CODES[$numberPref];
        
        $directionType = @self::CALL_DIR_TYPES[$directionId];

        if (!$directionType) return $typeId ? -1 : 'undefined';

        return $typeId ? $directionId : $directionType;
    }

    protected function validate($data, $type)
    {
        $validatorPath = '\\App\\Voip\\Validators\\';

        $validator = $validatorPath . (implode('', array_map('ucfirst', explode('_', $type)))) . 'Validator';

        if (!class_exists($validator)) return false;

        return $validator::validate($data);
    }
}
