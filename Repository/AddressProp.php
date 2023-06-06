<?php

namespace App\Repository;

use wf_JqDtHelper;

class AddressProp
{
    const TABLE = 'postcode_streets';

    protected $addresses = [];

    public function all($page = 1, $perPage = 1000)
    {
        $page = $page > 0 ? ($page - 1) * $perPage : 0;
        return $this->addresses = simple_queryall(
            "SELECT * FROM `" . self::TABLE . "` LIMIT $page, $perPage"
        );
    }

    public function search($search, $perPage = 1000)
    {
        return $this->addresses = simple_queryall(
            "SELECT * FROM `" . self::TABLE . "` " . self::searchWhere($search) . " LIMIT $perPage"
        );
    }

    public function findOne($id)
    {
        $id = vf($id, 3);
        return simple_query(
            "SELECT * FROM `" . self::TABLE . "` WHERE `id` = $id"
        );
    }

    public function total($search = '')
    {
        return simple_query(
            "SELECT COUNT(*) AS `total` FROM `" . self::TABLE . "` " . self::searchWhere($search)
        )['total'];
    }

    public function active($id, $status, $connType = null)
    {
        $id = vf($id, 3);
        $status = vf($status, 3);
        $connType = vf($connType) ? strtoupper($connType) : null;
        $response = [];
        if ($this->findOne($id)) {
            simple_update_field(self::TABLE, 'active', $status, "WHERE `id` = $id");
            simple_update_field(self::TABLE, 'conn_type', $connType, "WHERE `id` = $id");
            $response = ['id' => $id, 'status' => $status];
        }
        die(json_encode($response));
    }

    public function renderAddressesAjaxList($page, $perPage, $search = '')
    {
        $json = new wf_JqDtHelper;
        if (!$this->addresses) {
            if (empty($search)) {
                $this->all($page, $perPage);
                $total = $this->total();
            } else {
                $this->search($search, $perPage);
                $total = count($this->addresses);
            }
        }
        if ($this->addresses) {
            foreach ($this->addresses as $address) {
                $json->addRow($address);
            }
        }
        $json->getJson($total);
    }

    protected static function searchWhere($search)
    {
        $result = '';
        $search = vf($search);
        if (!empty($search)) {
            $result = "
                WHERE `id` LIKE '%$search%' 
                OR `uprn` LIKE '%$search%' 
                OR `singleline_address` LIKE '%$search%' 
                OR `postcode` LIKE '%$search%' 
                OR `district` LIKE '%$search%' 
            ";
        }
        return $result;
    }
}
