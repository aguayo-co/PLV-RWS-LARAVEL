<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserModelFilesTest extends TestCase
{
    public function testCoverImagePersistedOnExisting()
    {
        $user = factory(User::class)->create();

        $user->cover = UploadedFile::fake()->image('cover');
        $this->assertTrue(Storage::exists(Storage::files($user->getBasePathFor('cover'))[0]));
    }

    public function testPictureImagePersistedOnExisting()
    {
        $user = factory(User::class)->create();

        $user->picture = UploadedFile::fake()->image('picture');
        $this->assertTrue(Storage::exists(Storage::files($user->getBasePathFor('picture'))[0]));
    }

    public function testCoverImagePersistedOnNew()
    {
        $user = factory(User::class)->make();

        $user->cover = UploadedFile::fake()->image('cover');
        $user->save();
        $this->assertTrue(Storage::exists(Storage::files($user->getBasePathFor('cover'))[0]));
    }

    public function testPictureImagePersistedOnNew()
    {
        $user = factory(User::class)->make();

        $user->picture = UploadedFile::fake()->image('picture');
        $user->save();
        $this->assertTrue(Storage::exists(Storage::files($user->getBasePathFor('picture'))[0]));
    }

    public function testPictureDeleted()
    {
        $user = factory(User::class)->create();

        $user->picture = UploadedFile::fake()->image('picture');
        $user->picture = null;
        $this->assertFalse(Storage::exists($user->getBasePathFor('picture')));
    }

    public function testCoverDeleted()
    {
        $user = factory(User::class)->create();

        $user->cover = UploadedFile::fake()->image('cover');
        $user->cover = null;
        $this->assertFalse(Storage::exists($user->getBasePathFor('cover')));
    }

    public function testCoverPathRequiresId()
    {
        $user = factory(User::class)->make();

        $user->cover = UploadedFile::fake()->image('cover');
        $user->save();
        $this->assertTrue(Storage::exists(Storage::files($user->getBasePathFor('cover'))[0]));
    }

    public function testPicturePathRequiresId()
    {
        $user = factory(User::class)->make();

        $user->picture = UploadedFile::fake()->image('picture');
        $user->save();
        $this->assertTrue(Storage::exists(Storage::files($user->getBasePathFor('picture'))[0]));
    }
}
