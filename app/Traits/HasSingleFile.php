<?php

namespace App\Traits;

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
        $idPath = implode(str_split(str_pad($this->id, 9, 0, STR_PAD_LEFT), 3), '/');
        return 'public/' . $snakeClass . '/' . $snakeAttribute . '/' . $idPath . '/';
    }

    protected function setFile($attribute, $file)
    {
        if ($this->saveLater($attribute, $file)) {
            return;
        }

        $path = $this->getBasePathFor($attribute);
        Cache::forget($path);

        Storage::deleteDirectory($path);
        if ($file) {
            $filename = uniqid() . '.' . $file->extension();
            Storage::putFileAs($path, $file, $filename);
        }
        # Timestamps might not get updated if this was the only attribute that
        # changed in the model. Force timestamp update.
        $this->updateTimestamps();
    }

    protected function setContentToFile($attribute, $content, $ext)
    {
        if ($this->saveLater($attribute, $content)) {
            return;
        }

        $path = $this->getBasePathFor($attribute);
        Cache::forget($path);

        Storage::deleteDirectory($path);
        if ($content) {
            $filename = uniqid() . '.' . $ext;
            Storage::put($path . $filename, $content);
        }
        # Timestamps might not get updated if this was the only attribute that
        # changed in the model. Force timestamp update.
        $this->updateTimestamps();
    }


    protected function getFileUrl($attribute)
    {
        $url = null;

        $path = $this->getBasePathFor($attribute);
        if ($url = Cache::get($path)) {
            return $url;
        }

        if ($files = Storage::files($path)) {
            $url = asset(Storage::url($files[0]));
        }
        cache::put($path, $url, 1440);
        return $url;
    }
}
