<?php

namespace Jmdweb\Installer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class JMDWebInstallCommand extends Command
{
    use InstallBreezeInertia, DownloadModule, UpdateComposerFile, CopyWebpackMix, CopyAssets;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jmdweb:install {stack=cms}  {--composer=global : Absolute path to the Composer binary which should be used to install packages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
       
        
        // check if installer has development 
        
        $this->downloadModuleFromServer();

        $this->updateComposerFile();
        
        $this->copyWebpackMixjsToRootFile();

        // Middleware...
        $this->installMiddlewareAfter('SubstituteBindings::class', '\Jmdweb\Base\Http\Middleware\HandleInertiaRequests::class');

        // auth.php
        $this->updateAuthConfig();
        // DatabaseSeeder.php
        $this->updateAppSeederToDatabaseSeeder();

        $this->copyAssets();
        
        $this->installBreezeInertia();
    }

    /**
     * Installs the given Composer Packages into the application.
     *
     * @param  mixed  $packages
     * @return void
     */
    protected function requireComposerPackages($packages)
    {
        $composer = $this->option('composer');

        if ($composer !== 'global') {
            $command = ['php', $composer, 'require'];
        }

        $command = array_merge(
            $command ?? ['composer', 'require'],
            is_array($packages) ? $packages : func_get_args()
        );

        (new Process($command, base_path(), ['COMPOSER_MEMORY_LIMIT' => '-1']))
            ->setTimeout(null)
            ->run(function ($type, $output) {
                $this->output->write($output);
            });
    }

    /**
     * Update the "package.json" file.
     *
     * @param  callable  $callback
     * @param  bool  $dev
     * @return void
     */
    protected function updateNodePackages(callable $callback, $dev = true)
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages[$configurationKey] = $callback(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    /**
     * Install the middleware to a group in the application Http Kernel.
     *
     * @param  string  $after
     * @param  string  $name
     * @param  string  $group
     * @return void
     */
    protected function installMiddlewareAfter($after, $name, $group = 'web')
    {
        $httpKernel = file_get_contents(app_path('Http/Kernel.php'));

        $middlewareGroups = Str::before(Str::after($httpKernel, '$middlewareGroups = ['), '];');
        $middlewareGroup = Str::before(Str::after($middlewareGroups, "'$group' => ["), '],');

        if (! Str::contains($middlewareGroup, $name)) {
            $modifiedMiddlewareGroup = str_replace(
                $after.',',
                $after.','.PHP_EOL.'            '.$name.',',
                $middlewareGroup,
            );

            file_put_contents(app_path('Http/Kernel.php'), str_replace(
                $middlewareGroups,
                str_replace($middlewareGroup, $modifiedMiddlewareGroup, $middlewareGroups),
                $httpKernel
            ));
        }
    }

    public function updateAuthConfig()
    {
        $authConfig = file_get_contents(base_path('config/auth.php'));
        
        if(Str::contains($authConfig, 'App\Models\User::class')) {
            $authConfig = str_replace('App\Models\User::class', 'Jmdweb\User\Models\User::class', $authConfig);
            file_put_contents(base_path('config/auth.php'), $authConfig);
        }
    }

    public function updateAppSeederToDatabaseSeeder()
    {
        $seederPath = base_path('database/seeders/DatabaseSeeder.php');
        $seeders = file_get_contents($seederPath);

        if(Str::contains($seeders, '// \App\Models\User::factory(10)->create();')) {
            $seeders = str_replace('// \App\Models\User::factory(10)->create();', '$this->call(\Jmdweb\Base\database\seeds\JmdwebDatabaseSeeder::class);', $seeders);
            file_put_contents($seederPath, $seeders);
        }
        
    }
}
