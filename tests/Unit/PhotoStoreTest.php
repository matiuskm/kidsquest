<?php

namespace Tests\Unit;

use App\Exceptions\InvalidImage;
use App\Services\PhotoStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoStoreTest extends TestCase
{
    private PhotoStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->store = new PhotoStore;
    }

    private function dimensions(string $path): array
    {
        [$w, $h, $type] = getimagesizefromstring(Storage::disk('local')->get($path));

        return [$w, $h, $type];
    }

    public function test_large_jpeg_becomes_webp_with_longest_side_1280(): void
    {
        $file = UploadedFile::fake()->image('big.jpg', 4000, 3000);

        $path = $this->store->store($file);

        $this->assertStringEndsWith('.webp', $path);
        [$w, $h, $type] = $this->dimensions($path);
        $this->assertSame([1280, 960], [$w, $h]);
        $this->assertSame(IMAGETYPE_WEBP, $type);
        $this->assertLessThan($file->getSize(), Storage::disk('local')->size($path));
    }

    public function test_small_image_is_not_upscaled(): void
    {
        $path = $this->store->store(UploadedFile::fake()->image('s.png', 800, 600));

        $this->assertSame([800, 600], array_slice($this->dimensions($path), 0, 2));
    }

    public function test_exif_orientation_is_applied(): void
    {
        $this->assertSame([100, 200], array_slice($this->dimensions($this->store->store($this->rotatedJpeg())), 0, 2));
    }

    public function test_non_image_throws_and_writes_nothing(): void
    {
        $this->expectException(InvalidImage::class);

        try {
            $this->store->store(UploadedFile::fake()->createWithContent('a.jpg', 'ini bukan gambar'));
        } finally {
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }

    public function test_delete_removes_file(): void
    {
        $path = $this->store->store(UploadedFile::fake()->image('a.jpg', 100, 100));
        $this->store->delete($path);

        Storage::disk('local')->assertMissing($path);
    }

    /** JPEG landscape 200x100 dengan EXIF Orientation=6 (harus tampil portrait 100x200). */
    private function rotatedJpeg(): UploadedFile
    {
        $im = imagecreatetruecolor(200, 100);
        ob_start();
        imagejpeg($im);
        $jpeg = ob_get_clean();

        $tiff = "MM\x00\x2A\x00\x00\x00\x08"."\x00\x01"."\x01\x12\x00\x03\x00\x00\x00\x01\x00\x06\x00\x00"."\x00\x00\x00\x00";
        $app1 = "\xFF\xE1".pack('n', 2 + 6 + strlen($tiff))."Exif\x00\x00".$tiff;

        return UploadedFile::fake()->createWithContent('rot.jpg', substr($jpeg, 0, 2).$app1.substr($jpeg, 2));
    }
}
