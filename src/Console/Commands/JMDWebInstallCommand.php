<?php

namespace Jmdweb\Installer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\TransferStats;

class JMDWebInstallCommand extends Command
{
    use InstallBreezeInertia, DownloadModule, UpdateComposerFile, CopyWebpackMix, CopyAssets;

    public $serverUrl = "https://newcms.jmddesign.nl";

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

        $uri = $this->validateLicense();
        
        // check if installer has development 
        
        $this->downloadModuleFromServer($uri);
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

    public function validateLicense()
    {
        $verified = false;
        $domain = $this->askInstallationDomain();//"http://jmdweb.nl";
        
        while(!$verified) {
    
            $license = $this->askLicenseKey(); //"1re2adwktRBqzqnAJIdJCA==";
    
    
            $response = $this->checkIfLicenseIsValid($domain, $license);
            
            $status = false;
            $message = '';
            if(is_array($response)) {
                $status = $response['status'] == 'success';
                $message = $response['message'] ?? '';
            }

            $verified = $status;
            if (!empty($message)) {
                $this->line($message, $status ? 'info' : 'error');
            }
        }

        return $this->serverUrl."/download/cms?action=download&license_key=".$license;

    }

    /**
     * @return string
     */
    protected function askInstallationDomain()
    {
        do {
            $domain = $this->ask('Enter your application domain (the domain that needs to be attached to your license)');

            $validator = Validator::make(['domain' => $domain], [
                'domain' => ['required', 'url', function ($attribute, $value, $fail) {
                    if ($this->strpos_arr($value)) {
                        $fail($attribute . ' not a valid live url.');
                    }
                },],
            ]);

            if ($validator->fails()) {
                $this->warn(join('|', $validator->errors()->get('domain')));
                $domain = '';
            }
        } while (empty($domain));

        return $domain;
    }

    /**
     * @return string
     */
    public function askLicenseKey()
    {
        do {
            $license = $this->ask('Enter license code to download module');

            $validator = Validator::make(['license' => $license], [
                'license' => 'required',
            ]);

            if ($validator->fails()) {
                $this->warn(join('|', $validator->errors()->get('license')));
                $license = '';
            }
        } while (empty($license));

        return $license;
    }

    private function strpos_arr($domain)
    {
        $haystack = ['localhost', '.loc', '.dev', '127.0.0.1', 'dev.', 'test'];

        foreach ($haystack as $what) {
            if (($pos = strpos($domain, $what)) !== false) return true;
        }

        return false;
    }

    /**
     * @param $domain
     * @param $license
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function checkIfLicenseIsValid($domain, $license)
    {
        try {
            $client = new \GuzzleHttp\Client();
            
            $res = $client->request('GET', $this->serverUrl."/download/cms", [
                'query' => [
                    'license_key' => $license,
                    'domain' => $domain,
                    'action' => 'checkLicense',
                    'laravel_version' => app()->version(),
                ],
                'on_stats' => function (TransferStats $stats) use (&$url) {
                    $url = $stats->getEffectiveUri();
                }
            ]);
            $check_updates_result = json_decode($res->getBody(), true);
            return $check_updates_result;
        } catch (Exception $e) {
            $this->command->error($e->getMessage());
            return false;
        }
    }
}
