<?php

namespace App\Controllers\Api;

use App\Filters\PhotoStorageFilter;
use App\Controllers\AbstractController;
use App\Interfaces\PhotoStorageInterface;

class PhotostorageController extends AbstractController
{
    const ELLOWED_EXT = ["jpg", "gif", "png", "jpeg"];
    const STORAGE_PATH = 'content/documents/photostorage/';

    protected $fileName = 'images';
    protected $returnItems = false;

    public function all(PhotoStorageInterface $storage, $filters = [])
    {
        $validator = $this->validator(array_merge($this->get(), $filters), [
            'item' => 'integer|nullable',
            'scope' => 'string|nullable',
        ]);

        if (!$storage->itemId) $storage->itemId = 'null';
        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ]));
        }

        $storage->reloadImages(true);
        $photos = PhotoStorageFilter::filter(
            collect($storage->allimages),
            $this->validated()
        );

        if ($this->returnItems) return $photos;
        return $this->send(
            $this->response()->json($photos->toArray())
        );
    }

    public function update(PhotoStorageInterface $storage, $filters = [], $deleteOld = false)
    {
        $validator = $this->validator(
            array_merge($this->get(), $this->post(), $filters),
            [
                'item' => 'integer|required',
                'scope' => 'string|required',
                $this->fileName => 'array|nullable',
            ]
        );

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ]));
        }

        $validated = $this->validated();

        if ($deleteOld) {
            $storage->reloadImages(true);
            $photos = PhotoStorageFilter::filter(
                collect($storage->allimages),
                $validated
            );
            $photos->each(function ($item) use ($storage) {
                $storage->proxy->catchDeleteImage($item['id'], false);
            });
        }

        if ($this->files() || $this->files($this->fileName)) {
            $this->identFileName()->upload($storage);
        }

        $storage->reloadImages(true);
        $photos = PhotoStorageFilter::filter(
            collect($storage->allimages),
            $validated
        );

        return $this->send(
            $this->response()->json([
                'status' => true,
                $this->fileName => $photos->toArray()
            ])
        );
    }

    public function delete(PhotoStorageInterface $storage, $filters = [])
    {
        $validator = $this->validator(array_merge($this->get(), $filters), [
            'id' => 'integer|required',
            'item' => 'integer|required',
            'scope' => 'string|required',
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ]));
        }

        $storage->reloadImages(true);
        $photos = PhotoStorageFilter::filter(
            collect($storage->allimages),
            $this->validated()
        );

        $photos->each(function ($item) use ($storage) {
            if ($this->validated('id') === $item['id']) {
                $storage->proxy->catchDeleteImage($item['id'], false);
            }
        });

        return $this->send(
            $this->response()->json(['status' => true])
        );
    }

    public function deleteMultiple(PhotoStorageInterface $storage, $filters = [])
    {
        $deleted = [];
        $validator = $this->validator(array_merge($this->get(), $filters), [
            'id' => 'array|required',
            'item' => 'integer|required',
            'scope' => 'string|required',
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ]));
        }

        $storage->reloadImages(true);
        $photos = PhotoStorageFilter::filter(
            collect($storage->allimages),
            $this->validated()
        );

        $photos->each(function ($item) use ($storage, &$deleted) {
            if (in_array($item['id'], $this->validated('id'))) {
                $deleted[] = $item['id'];
                $storage->proxy->catchDeleteImage($item['id'], false);
            }
        });

        return $this->send(
            $this->response()->json([
                'status' => true,
                'id' => $deleted,
            ])
        );
    }

    public function upload(PhotoStorageInterface $storage, $images = [], $key = null)
    {
        $rules = [];
        $uploaded = 0;
        $images = $images ? $images : $this->files();
        $images = is_array($images[$this->fileName]) ? $images : [$this->fileName => [$images[$this->fileName]]];

        foreach (range(0, count($images[$this->fileName]) - 1) as $index) {
            $rules[$this->fileName . '.' . $index] = 'required|mimes:png,jpeg,jpg,gif';
        }

        $validator = $this->validator($images, $rules);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ]));
        }

        foreach ($images[$this->fileName] as $image) {
            try {
                $storage->proxy->setPhotoCoordinates(
                    $image->getPathname(),
                    $image->getClientMimeType(),
                    $this->post('latitude'),
                    $this->post('longitude')
                );
                $newFilename = zb_rand_string(16) . '_upload.jpg';
                // $moved = $image->move(self::STORAGE_PATH, $newFilename);
                $moved = move_uploaded_file($image->getPathname(), self::STORAGE_PATH . $newFilename);

                if ($image->getClientSize() > 1024000) {
                    list($width, $height) = @getimagesize($image->getPathname());
                    $storage->imageResizeTo(self::STORAGE_PATH . $newFilename, $width, $height, 1);
                }

                $storage->registerImage($newFilename);

                // move image to external space
                if ($storage->proxy) {
                    $storage->proxy->catchFileMove(self::STORAGE_PATH . $newFilename, $newFilename);
                }

                $uploaded++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $uploaded;
    }

    private function identFileName()
    {
        if (!empty($this->files())) {
            $this->fileName = array_key_first($this->files());
        }

        return $this;
    }

    public function returnItems($status = true)
    {
        $this->returnItems = $status;

        return $this;
    }
}
