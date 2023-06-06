<?php

namespace App\Interfaces;

interface CarsInterface
{
    public function createCar();
    public function getAllCars();
    public function editCar();
    public function deleteCars($id);
}
