<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ImageService
{
    public function createOrUpdateImage($image, $folder, $user = null)
    {
        if (isset($user) && $user->image) {
            if (Storage::exists("images/{$folder}/{$user->image}")) {
                Storage::delete("images/{$folder}/{$user->image}");
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

}
