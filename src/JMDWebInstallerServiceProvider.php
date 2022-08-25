<?php
namespace Jmdweb\Installer;

use Illuminate\Support\ServiceProvider;
use Jmdweb\Installer\Console\Commands\JMDWebInstallCommand;

class JMDWebInstallerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            JMDWebInstallCommand::class,
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [JMDWebInstallCommand::class];
    }

}
