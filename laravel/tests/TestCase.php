<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Strictness is pinned here as well as in the service provider, so the suite
     * refuses to lazy load whatever environment it is pointed at.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();

        // phpunit.xml already keeps media on a local disk, but a test that asks
        // for `s3` by name would still find real credentials sitting in .env.
        // Point the disk at a bucket that does not exist, so the only way to
        // exercise it is Storage::fake() — and a slip fails instead of landing
        // a stray object in the real one.
        config([
            'filesystems.disks.s3.key' => 'testing',
            'filesystems.disks.s3.secret' => 'testing',
            'filesystems.disks.s3.bucket' => 'testing',
            'filesystems.disks.s3.endpoint' => 'http://s3.invalid',
            'filesystems.disks.s3.use_path_style_endpoint' => true,
        ]);
    }
}
