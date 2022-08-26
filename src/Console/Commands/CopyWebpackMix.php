<?php

namespace Jmdweb\Installer\Console\Commands;

trait CopyWebpackMix
{
    public function copyWebpackMixjsToRootFile()
    {
        $this->info("Updating the webpack.mix.js file...");

        if (! file_exists(base_path('webpack.mix.js'))) {
            return;
        }

        $update_mix = file_get_contents(base_path('Jmdweb/webpack.mix.js'));

        $mix = file_put_contents(base_path('webpack.mix.js'), $update_mix.PHP_EOL , FILE_APPEND | LOCK_EX);


        dd($update_mix);
    }
}