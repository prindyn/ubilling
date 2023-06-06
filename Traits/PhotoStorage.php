<?php

namespace App\Traits;

use App\Controllers\Api\PhotostorageController;
use App\Repository\PhotoStorageProxy;

trait PhotoStorage
{
    protected $photoStorage;

    protected $photoStorageProxy;

    private $service = 'digital-ocean';

    public function photoStorageProxy($scope, $itemId, $version = null, $service = null)
    {
        $this->photoStorageProxy = new PhotoStorageProxy(
            $scope,
            $itemId,
            $version = $version ? $version : 2,
            $service = $service ? $service : $this->service
        );

        return $this->photoStorageProxy;
    }

    public function photoStorage()
    {
        $this->photoStorage = new PhotostorageController();

        return $this->photoStorage;
    }
}
