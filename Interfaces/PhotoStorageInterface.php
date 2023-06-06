<?php

namespace App\Interfaces;

interface PhotoStorageInterface
{
    function loadAllImages();

    function catchDeleteImage($id);

    function catchDeleteImageScope();

    function catchDownloadImage($id);

    function catchDeleteImageByItem($item);

    function catchDeleteImageByKey($key, $die);

    public function reloadImages($force);
}
