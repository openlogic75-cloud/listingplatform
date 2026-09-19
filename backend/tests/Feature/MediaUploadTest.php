<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    private function registerWorker(): string
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Uploader U',
            'email' => 'uploader@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'skilled_worker',
        ]);

        $response->assertCreated();

        return (string) $response->json('token');
    }

    public function test_a_png_upload_is_validated_converted_to_webp_and_recorded(): void
    {
        $token = $this->registerWorker();
        Storage::fake('public');

        $response = $this->withToken($token)->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->image('product.png', 320, 240),
            'directory' => 'products',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['id', 'path', 'url']);

        $path = (string) $response->json('path');
        $this->assertStringStartsWith('products/', $path);
        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame('image/webp', (string) $response->json('mime'));

        // The stored bytes really are WebP (RIFF....WEBP container).
        $bytes = (string) Storage::disk('public')->get($path);
        $this->assertSame('RIFF', substr($bytes, 0, 4));
        $this->assertSame('WEBP', substr($bytes, 8, 4));

        $this->assertDatabaseHas('media', [
            'path' => $path,
            'mime_type' => 'image/webp',
            'uploaded_by' => User::query()->firstOrFail()->id,
        ]);

        // The recorded size is the stored (converted) file, not the original.
        $storedFile = Storage::disk('public')->path($path);
        clearstatcache(true, $storedFile);
        $recordedSize = (int) DB::table('media')->where('path', $path)->value('size');
        $this->assertGreaterThan(0, $recordedSize);
        $this->assertSame((int) filesize($storedFile), $recordedSize);
    }

    public function test_a_jpeg_upload_is_converted_to_webp(): void
    {
        $token = $this->registerWorker();
        Storage::fake('public');

        $response = $this->withToken($token)->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->image('photo.jpg', 640, 480),
            'directory' => 'products',
        ]);

        $response->assertCreated();

        $this->assertStringEndsWith('.webp', (string) $response->json('path'));
        $this->assertSame('image/webp', (string) $response->json('mime'));
    }

    /**
     * Transparency must survive the WebP conversion (transparent PNGs are
     * common product images).
     */
    public function test_transparency_survives_the_webp_conversion(): void
    {
        $token = $this->registerWorker();
        Storage::fake('public');

        // 2x1 PNG: left pixel fully transparent, right pixel opaque.
        $image = imagecreatetruecolor(2, 1);
        imagesavealpha($image, true);
        imagealphablending($image, false);
        imagefilledrectangle($image, 0, 0, 1, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagesetpixel($image, 1, 0, imagecolorallocatealpha($image, 200, 30, 30, 0));
        $source = tempnam(sys_get_temp_dir(), 'alpha').'.png';
        imagepng($image, $source);
        imagedestroy($image);

        $response = $this->withToken($token)->postJson('/api/v1/media', [
            'file' => new UploadedFile($source, 'transparent.png', 'image/png', null, true),
            'directory' => 'products',
        ]);

        $response->assertCreated();

        $decoded = imagecreatefromstring(
            (string) Storage::disk('public')->get((string) $response->json('path'))
        );
        $this->assertNotFalse($decoded, 'Stored file decodes as an image');

        // GD alpha runs 0 (opaque) to 127 (fully transparent).
        $transparent = imagecolorsforindex($decoded, imagecolorat($decoded, 0, 0));
        $opaque = imagecolorsforindex($decoded, imagecolorat($decoded, 1, 0));

        imagedestroy($decoded);
        @unlink($source);

        $this->assertGreaterThan(100, $transparent['alpha'], 'Transparent pixel stayed transparent');
        $this->assertLessThan(20, $opaque['alpha'], 'Opaque pixel stayed opaque');
    }

    public function test_an_already_webp_upload_stays_webp(): void
    {
        $token = $this->registerWorker();
        Storage::fake('public');

        $response = $this->withToken($token)->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->image('shot.webp', 320, 240),
            'directory' => 'products',
        ]);

        $response->assertCreated();

        $this->assertStringEndsWith('.webp', (string) $response->json('path'));
        $this->assertSame('image/webp', (string) $response->json('mime'));
    }

    public function test_an_oversized_image_is_rejected(): void
    {
        $token = $this->registerWorker();

        $this->withToken($token)->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->image('huge.jpg', 5000, 5000),
        ])->assertUnprocessable();
    }

    public function test_a_non_image_file_is_rejected(): void
    {
        $token = $this->registerWorker();

        $this->withToken($token)->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->create('payload.pdf', 200, 'application/pdf'),
        ])->assertUnprocessable();

        $this->withToken($token)->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->createWithContent(
                'evil.png',
                "<?php echo 'not an image';"
            ),
        ])->assertUnprocessable();
    }

    public function test_uploads_require_authentication(): void
    {
        $this->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->image('anon.png'),
        ])->assertUnauthorized();
    }

    public function test_listing_image_paths_must_belong_to_the_uploading_user(): void
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Vendor V',
            'email' => 'vendor-media@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'vendor',
            'display_name' => 'Media Farm',
            'vendor_category' => 'agro',
        ]);

        $register->assertCreated();
        $token = (string) $register->json('token');

        $this->withToken($token)->postJson('/api/v1/listings', [
            'title' => 'Fake image path',
            'category' => 'agro',
            'images' => ['products/does-not-exist.jpg'],
        ])->assertCreated();

        // The bogus path was dropped from the stored listing.
        $this->assertSame([], json_decode((string) DB::table('products')->value('images'), true));
    }
}
