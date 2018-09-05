<?php

namespace ObsNomad\SSO;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom($this->packagePath('src/Http/routes.php'));
        $this->publishConfig();
    }

    private function publishConfig()
    {
        $client = $this->packagePath('config/ssoclient.php');
        $server = $this->packagePath('config/ssoserver.php');

        $this->publishes([
            $client => config_path('ssoclient.php'),
            $server => config_path('ssoserver.php'),
        ], 'config');

        $this->mergeConfigFrom($client, 'ssoclient');
        $this->mergeConfigFrom($server, 'ssoserver');
    }

    private function packagePath($path)
    {
        return __DIR__ . "/../$path";
    }
}
