<?php

namespace App\Repository;

use App\Interfaces\CarsInterface;
use App\Repository\PhotoStorageProxy;
use wf_JqDtHelper;


class Cars implements CarsInterface
{
    /**
     * Contains all cars
     *
     * @var array
     */
    protected $allCars = array();

    /**
     * Creates new cars instance
     *
     * @return void
     */
    public function __construct()
    {
        $this->getAllCars();
    }

    /**
     * Get all cars with database
     *
     * @return array
     */
    public function getAllCars()
    {
        $query = "SELECT * from `cars`";
        $all = simple_queryall($query);

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allCars[$each['id']] = $each;
            }
        }

        return $this->allCars;
    }

    /**
     * Get all name cars
     * @param array $cars
     *
     * @return array
     */
    public function getNameCars($cars)
    {
        $carName = [];
        if (!empty($cars)) {
            foreach ($cars as $id => $carItem) {
                $carName[$id] = $carItem['car_name'] .  ' '  . $carItem['model_car'] . ' ' . $carItem['number_car'];
            }
        }

        return $carName;
    }

    public function getResponsibilityCars($idEmployee = 0, $arrKey = 'id')
    {
        $responsibilityCars = [];
        $query = "SELECT `cars_admin`.`cars_id`, `cars`.*
                FROM `cars_admin`
                LEFT JOIN `cars` ON `cars_admin`.`cars_id` = `cars`.`id` 
                WHERE `cars_admin`.`admin_id` = '" . $idEmployee . "'";
        $cars = simple_queryall($query);

        if (!empty($cars)) {
            foreach ($cars as $id => $carItem) {
                $responsibilityCars[$carItem[$arrKey]] = $carItem['car_name'] .  ' '  . $carItem['model_car'] . ' ' . $carItem['number_car'];
            }
        }

        return $responsibilityCars;
    }

    /**
     * Create new cars
     * @param string $nameCar
     * @param string $numberCar
     * @param string $modelCar
     * @param string $description
     * @param string $userCreater
     *
     * @return boolean
     */
    public function createCar()
    {
        $nameCar = vf($_POST['carname']);
        $numberCar = vf($_POST['carnumber']);
        $modelCar = vf($_POST['carmodel']);
        $description = vf($_POST['cardescription']);
        $admin = vf(whoami());

        $nameCar = mysql_real_escape_string($nameCar);
        $numberCar = mysql_real_escape_string($numberCar);
        $modelCar = mysql_real_escape_string($modelCar);
        $description = mysql_real_escape_string($description);
        $admin = mysql_real_escape_string(whoami());

        $query = "INSERT INTO `cars` (`id`, `car_name`, `number_car`, `model_car`, `description`, `admin`) "
            . "VALUES (NULL, '" . $nameCar . "', '" . $numberCar . "', '" . $modelCar . "', '" . $description . "', '" . $admin . "');";
        $result = nr_query($query);

        $newId = simple_get_lastid('cars');
        log_register('CARS CREATE [' . $newId . '] CARNAME [' . $nameCar . '] NUMBERCAR `' . $numberCar . '` MODELCAR `' . $modelCar . '`');

        if (wf_CheckGet(array('ajax'))) die(json_encode(array('id' => get_last_id('cars'))));

        return !empty($result) ? true : false;
    }

    /**
     * Edit car with database
     *
     * @return boolean
     */
    public function editCar()
    {
        $id = vf($_POST['id']);
        $newNameCar = vf($_POST['carname']);
        $newNumberCar = vf($_POST['carnumber']);
        $newModelCar = vf($_POST['carmodel']);
        $newDescription = vf($_POST['cardescription']);
        $newAdmin = isset($_POST['admin']) ? vf($_POST['admin']) : '';

        if (isset($this->allCars[$id])) {
            $where = " WHERE `id`='" . $id . "'";

            simple_update_field('cars', 'car_name', $newNameCar, $where);
            simple_update_field('cars', 'number_car', $newNumberCar, $where);
            simple_update_field('cars', 'model_car', $newModelCar, $where);
            simple_update_field('cars', 'description', $newDescription, $where);
            if ($newAdmin) {
                simple_update_field('cars', 'description', $newAdmin, $where);
            }

            log_register('CARS EDIT [' . $id . '] `' . $newNameCar . '`');

            $result = true;
        } else {
            log_register('CARS EDIT FAIL [' . $id . '] NO_EXISTING');

            $result = false;
        }
        if (wf_CheckGet(array('ajax'))) die(json_encode(array('id' => $id)));

        return $result;
    }

    /**
     * Edit secondary responsible employee for the car
     *
     * @return boolean
     */
    public function secondaryResponsible($idEmployee, $idCars)
    {
        $query = '';
        $queryDelete = '';

        if (!empty($idEmployee)) {
            $queryDelete = "DELETE FROM `cars_admin` WHERE admin_id = '" . $idEmployee . "';";
            nr_query($queryDelete);
            if (!empty($idCars)) {
                foreach ($idCars as $index => $id) {
                    $query = "INSERT INTO `cars_admin` (`id`,`cars_id`, `admin_id`) "
                        . "VALUES (NULL, '" . $id . "', '" . $idEmployee . "');";
                    nr_query($query);
                }
            }
        }
    }

    public function renderCarsAjaxList()
    {
        $json = new wf_JqDtHelper();

        if (!empty($this->allCars)) {
            foreach ($this->allCars as $index => $carItem) {
                if ($carItem > 0) {
                    $data[] = $carItem['id'];
                    $data[] = $carItem['car_name'];
                    $data[] = $carItem['number_car'];
                    $data[] = $carItem['model_car'];
                    $data[] = $carItem['description'];
                    $data[] = $carItem['admin'];

                    $json->addRow($data);
                    unset($data);
                }
            }
        }

        $json->getJson();
    }

    public function renderOneCarAjaxList($id)
    {
        $id = vf($id, 3);
        $data = array();

        if (!empty($this->allCars)) {
            foreach ($this->allCars as $index => $carItem) {
                if ($carItem > 0 && $carItem['id'] == $id) {
                    $data['id'] = $carItem['id'];
                    $data['carname'] = $carItem['car_name'];
                    $data['carnumber'] = $carItem['number_car'];
                    $data['carmodel'] = $carItem['model_car'];
                    $data['cardescription'] = $carItem['description'];
                    $data['admin'] = $carItem['admin'];
                }
            }
        }

        if (wf_CheckGet(array('ajax'))) die(json_encode($data));

        return $data;
    }

    public function renderImagesListJson($id)
    {
        $result = ['main' => NULL, 'extra' => []];

        if (isset($this->allCars[$id])) {
            $photostorage = new PhotoStorageProxy('NOI', '', 2, 'digital-ocean');
            $photostorage->setScope('CAR')->setItemid($id);

            foreach ($photostorage->findByItem() as $eachImg) {
                $eachImg['filename'] = $eachImg['filename'];
                $result['extra'][] = $eachImg;
            }
        }

        die(json_encode($result));
    }

    /**
     * Delete car with database
     * @param int $id
     *
     * @return boolean
     */
    public function deleteCars($id)
    {
        $id = vf($id, 3);
        if (isset($this->allCars[$id])) {
            $query = "DELETE FROM `cars` WHERE `id`='" . $id . "';";
            nr_query($query);
            log_register('CARS DELETE [' . $id . ']');
            $result =  true;
        } else {
            $result = false;
        }

        if (wf_CheckGet(array('ajax'))) die(json_encode(array('id' => $id)));

        return ($result);
    }
}
