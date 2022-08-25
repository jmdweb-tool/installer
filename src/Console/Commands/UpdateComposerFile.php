<?php

namespace Jmdweb\Installer\Console\Commands;

trait UpdateComposerFile
{
    public function updateComposerFile()
    {
        $this->info("Updating the composer.json file...");

        if (! file_exists(base_path('composer.json'))) {
            return;
        }

        $update_composer = json_decode(file_get_contents(base_path('Jmdweb/composer.json')), true);
        
        $keys = ['autoload', 'extra', 'config'];
        
        $composers = $packages = json_decode(file_get_contents(base_path('composer.json')), true);

        foreach ($update_composer as $ckey => $composer) {
            if(in_array($ckey, $keys)) {
                $composers[$ckey] = $composer;
            }
        }

        file_put_contents(
            base_path('composer.json'),
            json_encode($composers, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
        
        
        $this->info("Updated composer.json file");
    }
}