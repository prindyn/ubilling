<?php

namespace App\Repository;

use SpacesAPI\Spaces;
use App\Interfaces\PhotoStorageInterface;

class PhotoStorageProxy implements PhotoStorageInterface
{
    /**
     * @var \App\Interfaces\PhotoStorageInterface $obj;
     */
    private $obj;

    private $space = null;

    private $externImages = [];

    public function __construct($scope = '', $itemId = '', $version = 0, $space = '')
    {
        $item = $itemId ? $itemId : $this->storageItem();

        $scope = $scope ? $scope : $this->storageScope();

        $storageVer = $version ? $version : $this->storageVersion();

        if ($storageVer == 1) {

            $this->obj = new \PhotoStorage($scope, $item);
        } elseif ($storageVer == 2) {

            $this->obj = new \PhotoStorage2($scope, $item);
        } else {
            throw new \Exception('Photo Storage version not set.');
        }
        $this->setSpace($space);

        $this->loadAllImages();

        $this->obj->proxy = $this;
    }

    public function storage()
    {
        return $this->obj;
    }

    public function setSpace($space)
    {
        switch ($space) {

            case 'digital-ocean':
                $this->space = (new Spaces($this->obj->photoCfg['DO_STORAGE_KEY'], $this->obj->photoCfg['DO_STORAGE_SECRET'], 'ams3'))->space('photostorage.bts');
                break;

            default:
                $this->space = null;
                break;
        }
    }

    public function loadAllImages()
    {
        $this->obj->loadAllImages();

        $this->externImages = $this->space ? $this->space->listFiles()['files'] : [];

        if (!empty($this->obj->allimages)) {

            foreach ($this->obj->allimages as &$eachImage) {
                $eachImage['shortname'] = $eachImage['filename'];

                if (isset($this->externImages[$eachImage['filename']])) {

                    $eachImage['filename'] = $this->externImages[$eachImage['filename']]->getSignedURL();
                } elseif (file_exists($this->obj::STORAGE_PATH . $eachImage['filename'])) {

                    $eachImage['filename'] = $this->obj::STORAGE_PATH . $eachImage['filename'];
                }
            }
        }
    }

    public function catchFileMove($filePath, $fileName)
    {
        if ($this->space && file_exists($filePath)) {

            $this->space->uploadFile($filePath, $fileName);

            unlink($filePath);
        }
    }

    public function catchDeleteImage($id, $die = true)
    {
        if (empty($this->obj->allimages)) $this->loadAllImages();

        if ($this->space && isset($this->obj->allimages[$id])) {

            $image = $this->obj->allimages[$id];

            if (isset($this->externImages[$image['shortname']])) {

                if (cfr('PHOTOSTORAGEDELETE')) {

                    $this->space->file($image['shortname'])->delete();

                    $this->obj->unregisterImage($id);
                } else {
                    $this->actionResponse('Access denied', 'alert_warning');
                }
                if ($die) $this->actionResponse('Deleted', 'alert_warning');
            }
        }
        return $this->obj->catchDeleteImage($id, $die);
    }

    public function catchDeleteImageByKey($key, $die = true)
    {
        $allImages = array_values($this->findByItem());

        if ($key !== false && isset($allImages[$key])) {

            return $this->catchDeleteImage($allImages[$key]['id'], $die);
        }
        return $this->obj->catchDeleteImageByKey($key, $die);
    }

    public function catchDeleteImageScope()
    {
        if (empty($this->obj->allimages)) $this->loadAllImages();

        if ($this->space) {

            foreach ($this->obj->allimages as $id => $eachImage) {

                if (isset($this->externImages[$eachImage['shortname']])) {

                    if (cfr('PHOTOSTORAGEDELETE')) {

                        $this->space->file($eachImage['shortname'])->delete();

                        $this->obj->unregisterImage($id);
                    } else {
                        $this->actionResponse('Access denied', 'alert_warning');
                    }
                } else {
                    $this->actionResponse('File not exist', 'alert_warning');
                }
            }
            $this->actionResponse('Deleted', 'alert_warning');
        }
        return $this->obj->catchDeleteImageScope();
    }

    public function catchDeleteImageByItem($item)
    {
        if (empty($this->obj->allimages)) $this->loadAllImages();

        if ($this->space) {

            if (cfr('PHOTOSTORAGEDELETE')) {

                foreach ($this->obj->allimages as $id => $eachImage) {

                    if ($eachImage['item'] != $item || strpos($eachImage['scope'], $this->obj->scope) === false) continue;

                    $this->catchDeleteImage($eachImage['id'], false);
                }
                $this->actionResponse('Deleted', 'alert_warning');
            } else {
                $this->actionResponse('Access denied', 'alert_warning');
            }
        }
        return $this->obj->catchDeleteImageByItem($item);
    }

    public function catchDownloadImage($id)
    {
        if ($this->space && isset($this->obj->allimages[$id])) {

            $image = $this->obj->allimages[$id];

            if (isset($this->externImages[$image['shortname']])) {

                $signedUrl = $this->space->file($image['shortname'])->getSignedURL();

                header('Content-Type: image/png');
                header("Content-Transfer-Encoding: Binary");
                header("Content-disposition: attachment; filename=\"" . basename(str_replace(['.jpg', '.jpeg'], '.png', $image['shortname'])) . "\"");
                header("Content-Description: File Transfer");

                die(readfile($signedUrl));
            }
        }
        return $this->obj->catchDownloadImage($id);
    }

    public function setPhotoCoordinates($fileName, $ext, $latitude = "", $longitude = "", $waterLogo = true)
    {
        if (file_exists($fileName)) {
            if (!empty($latitude) && !empty($longitude)) {
                if (ispos($ext, 'jpg') || ispos($ext, 'jpeg'))
                    $imageTmp = imagecreatefromjpeg($fileName);
                else if (ispos($ext, 'png'))
                    $imageTmp = imagecreatefrompng($fileName);
                else if (ispos($ext, 'gif'))
                    $imageTmp = imagecreatefromgif($fileName);
                else if (ispos($ext, 'bmp'))
                    $imageTmp = imagecreatefrombmp($fileName);
                else
                    return 0;
                $gpsString = "$latitude, $longitude";
                list($imgTmpW, $imgTmpH) = getimagesize($fileName);
                // creates rectangle gps coordinates canvas
                $fontSize = 5;
                $gpsCanvasH = 30;
                $gpsCanvasW = 200;
                $scaleIndex = ($imgTmpW / 1500) > 1 ? ($imgTmpW / 1500) : 1;
                $gpsCanvas = imagecreatetruecolor($gpsCanvasW, $gpsCanvasH);
                imagefill($gpsCanvas, 0, 0, imagecolorallocate($gpsCanvas, 255, 255, 255));
                $textColor = imagecolorallocate($gpsCanvas, 0, 0, 0);
                imagestring($gpsCanvas, $fontSize, 0, 5, $gpsString, $textColor);
                $gpsCanvas = imagescale($gpsCanvas, $gpsCanvasW * $scaleIndex, $gpsCanvasH * $scaleIndex);
                // merge gps coordinates
                imagecopymerge($imageTmp, $gpsCanvas, 0, $imgTmpH - $gpsCanvasH * $scaleIndex, 0, 0, $gpsCanvasW * $scaleIndex, $gpsCanvasH * $scaleIndex, 100);

                if ($waterLogo) {
                    $logoPath = 'skins/logo_white.png';
                    $stamp = imagecreatefrompng($logoPath);
                    list($stampW, $stampH) = getimagesize($logoPath);
                    $stamp = imagescale($stamp, $stampW * $scaleIndex, $stampH * $scaleIndex);
                    // copy the stamp image onto our photo using the margin offsets and the photo 
                    // width to calculate positioning of the stamp. 
                    imagecopy($imageTmp, $stamp, 10, 10, 0, 0, imagesx($stamp), imagesy($stamp));
                }
                imagejpeg($imageTmp, $fileName);
                imagedestroy($imageTmp);
            }
        }
    }

    public function fileExists($file)
    {
        return file_exists($file['filename']) || isset($this->externImages[$file['shortname']]);
    }

    public function __call($name, $arguments)
    {
        return $this->obj->$name(implode(', ', $arguments));
    }

    private function storageVersion()
    {
        return isset($_POST['storage_version']) ? $_POST['storage_version'] : (isset($_GET['storage_version']) ? $_GET['storage_version'] : 1);
    }

    private function storageItem()
    {
        return isset($_GET['itemid']) ? vf($_GET['itemid'], 3) : null;
    }

    private function storageScope()
    {
        return isset($_GET['scope']) ? vf($_GET['scope']) : null;
    }

    private function actionResponse($response, $type)
    {
        die(wf_tag('span', false, $type) . __($response) . wf_tag('span', true));
    }

    public function reloadImages($force = false)
    {
        return $this->obj->reloadImages($force);
    }
}
