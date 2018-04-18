<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

trait HasSingleFile
{
    use SaveLater;

    public function getBasePathFor($attribute)
    {
        if (!$this->id) {
            abort(500);
        }

        $snakeClass = snake_case(class_basename(self::class));
        $snakeAttribute = snake_case($attribute);
        return 'uploads/' . $snakeClass . '/' . $snakeAttribute . '/' . $this->id . '/';
    }

    protected function setFile($attribute, ?UploadedFile $file)
    {
        if ($this->saveLater($attribute, $file)) {
            return;
        }

        $path = $this->getBasePathFor($attribute);
        Cache::forget($path);

        Storage::deleteDirectory($path);
        if ($file) {
            $filename = uniqid() . '.' . $file->extension();
            $file->storeAs($path, $filename);
        }
        # Timestamps might not get updated if this was the only attribute that
        # changed in the model. Force timestamp update.
        $this->updateTimestamps();
    }

    protected function getFileUrl($attribute)
    {
        $path = $this->getBasePathFor($attribute);
        if ($url = Cache::get($path)) {
            return $url;
        }

        if ($files = Storage::files($path)) {
            $validUntil = now()->addDays(1);
            $url = Storage::temporaryUrl($files[0], $validUntil);
            cache::put($path, $url, now()->diffInMinutes($validUntil) - 1);
            return $url;
        }
        return;
    }
}
