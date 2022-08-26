<?php
namespace Jmdweb\Installer\Console\Commands;
use Illuminate\Filesystem\Filesystem;


trait CopyAssets {
    
    public function copyAssets(Type $var = null)
    {
        (new Filesystem)->ensureDirectoryExists(public_path('assets/img'));

        (new Filesystem)->copyDirectory(base_path("Jmdweb/stubs/Images"), public_path('assets/img'));
    }
}
