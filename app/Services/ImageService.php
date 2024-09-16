<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ImageService
{
    public function createOrUpdateImage($image, $folder, $data = null)
    {
        if (isset($data) && $data->image) {
            if (Storage::exists("images/{$folder}/{$data->image}")) {
                Storage::delete("images/{$folder}/{$data->image}");
            }
        }

        $ext = $image->extension();
        $nameFile = uniqid() . ".{$ext}";
        $imagePath = $image->storeAs("images/{$folder}", $nameFile);

        if (!$imagePath) {
            throw new \Exception('Falha no upload da imagem.');
        }

        return $nameFile;
    }

    public function deleteImage($folder, $imageName)
    {
        if (Storage::exists("images/{$folder}/{$imageName}")) {
            Storage::delete("images/{$folder}/{$imageName}");
        }

        return true;
    }

}
