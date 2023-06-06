<?php

namespace App\Controllers\Api;

use App\Controllers\AbstractController;
use App\Repository\WarehouseRepository;

class WarehouseController extends AbstractController
{
    /**
     * @var App\Repository\WarehouseRepository;
     */
    private $repository;

    public function __construct()
    {
        $this->repository = new WarehouseRepository();
    }

    public function getAllItems()
    {
        return $this->send(
            $this->response()->json($this->repository->getCategoriesArray('id', 'name'))
        );
    }
}