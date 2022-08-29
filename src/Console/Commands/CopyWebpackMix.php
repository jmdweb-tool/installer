<?php

namespace Jmdweb\Installer\Console\Commands;

use Illuminate\Support\Str;

trait CopyWebpackMix
{
    public function copyWebpackMixjsToRootFile()
    {
        $this->info("Updating the webpack.mix.js file...");

        if (! file_exists(base_path('webpack.mix.js'))) {
            return;
        }

        $update_mix = file_get_contents(base_path('Jmdweb/stubs/webpack.mix.js'));
        $check_if_already_have_the_module_script = file_get_contents(base_path('webpack.mix.js'));

       

        if(!Str::contains( $check_if_already_have_the_module_script,"Jmdweb/views/vue/app.js")) {
            file_put_contents(base_path('webpack.mix.js'), $update_mix.PHP_EOL , FILE_APPEND | LOCK_EX);
        }

    }
}