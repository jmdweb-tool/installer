<?php
namespace Jmdweb\Installer\Console\Commands;

trait InstallBreezeInertia {
    
    public function installBreezeInertia()
    {
        // Install Inertia...
        $this->requireComposerPackages('inertiajs/inertia-laravel:^0.5.4', 'laravel/sanctum:^2.8', 'tightenco/ziggy:^1.0', 'wikimedia/composer-merge-plugin:^2.0');

        copy(base_path('Jmdweb/stubs/package.json'), base_path('package.json'));
        
        // NPM Packages...
        // $this->updateNodePackages(function ($packages) {
        //     return [
        //         '@inertiajs/inertia' => '^0.11.0',
        //         '@inertiajs/inertia-vue3' => '^0.6.0',
        //         '@inertiajs/progress' => '^0.2.7',
        //         '@vitejs/plugin-vue' => '^2.3.3',
        //         'autoprefixer' => '^10.4.2',
        //         'postcss' => '^8.4.6',
        //         'vue' => '^3.2.31',
        //     ] + $packages;
        // });

        $this->info('Inertiajs added to package.json and composer.json.');
        $this->comment('Please execute the composer update && "npm install" && "npm run dev" commands to build your assets.');
    }
}