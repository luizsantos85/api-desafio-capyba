<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ImageService
{
    public function createOrUpdateImage($image, $user = null)
    {
        if (isset($user) && $user->image) {
            if (Storage::exists("images/{$user->image}")) {
                Storage::delete("images/{$user->image}");
            }
        }

        $ext = $image->extension();
        $nameFile = uniqid() . ".{$ext}";
        $imagePath = $image->storeAs('images', $nameFile);

        if (!$imagePath) {
            throw new \Exception('Falha no upload da imagem.');
        }

        return $nameFile;
    }

}
