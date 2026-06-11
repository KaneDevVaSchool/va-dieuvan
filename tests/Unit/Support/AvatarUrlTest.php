<?php

namespace Tests\Unit\Support;

use App\Support\AvatarUrl;
use PHPUnit\Framework\TestCase;

class AvatarUrlTest extends TestCase
{
    public function test_returns_relative_storage_path_from_absolute_url(): void
    {
        $this->assertSame(
            '/storage/avatars/1/photo.jpg',
            AvatarUrl::forClient('https://localhost:8000/storage/avatars/1/photo.jpg'),
        );
    }

    public function test_preserves_external_avatar_urls(): void
    {
        $google = 'https://lh3.googleusercontent.com/a/abc=s96-c';
        $this->assertSame($google, AvatarUrl::forClient($google));
    }

    public function test_null_and_empty(): void
    {
        $this->assertNull(AvatarUrl::forClient(null));
        $this->assertNull(AvatarUrl::forClient(''));
        $this->assertNull(AvatarUrl::forClient('   '));
    }
}
