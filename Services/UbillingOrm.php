<?php

namespace App\Services;

use NyanORM;

class UbillingOrm extends NyanORM
{
    protected $flushed = false;

    /**
     * Creates new database record for current model instance.
     * 
     * @param bool $autoAiId append default NULL autoincrementing primary key?
     * @param bool $flushParams flush all query parameters like where, order, limit and other after execution?
     * @param array $data data that should be created
     * 
     * @return bool|null
     */
    public function create($data = [], $autoAiId = true, $flushParams = false)
    {
        if ($data) {
            foreach ($data as $name => $value) {
                $this->data($name, $value);
            }
        }
        try {
            parent::create($autoAiId, $flushParams);
            $this->id = simple_get_lastid('payments');
            $this->where('id', '=', $this->id);
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Saves current model data fields changes to database.
     * 
     * @param bool $flushParams flush all query parameters like where, order, limit and other after execution?
     * @param bool $fieldsBatch gather all the fields together in a single query from $this->data structure
     *             before actually running the query to reduce the amount of subsequential DB queries for every table field
     * @param array $data data that should be updated
     * 
     * @return bool|null
     */
    public function save($data = [], $flushParams = false, $fieldsBatch = false)
    {
        if ($data) {
            foreach ($data as $name => $value) {
                $this->data($name, $value);
            }
        }
        try {
            parent::save($flushParams, $fieldsBatch);
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Returns fields count in datatabase instance
     * 
     * @param string $fieldsToCount field name to count results
     * @param bool  $flushParams flush all query parameters like where, order, limit and other after execution?
     * 
     * @return int
     */
    public function getFieldsCount($fieldsToCount = 'id', $flushParams = false)
    {
        try {
            return parent::getFieldsCount($fieldsToCount, $flushParams);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Returns fields sum in datatabase instance
     * 
     * @param string $fieldsToSum field name to retrive its sum
     * @param bool  $flushParams flush all query parameters like where, order, limit and other after execution?
     * 
     * @return int
     */
    public function getFieldsSum($fieldsToSum, $flushParams = false)
    {
        try {
            return parent::getFieldsSum($fieldsToSum, $flushParams);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function isFlushed()
    {
        return $this->flushed;
    }

    public function getData()
    {
        return $this->data;
    }

    protected function destroyAllStructs()
    {
        try {
            $this->flushed = true;
            return parent::destroyAllStructs();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
