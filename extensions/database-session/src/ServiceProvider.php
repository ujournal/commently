<?php

namespace Commently\DatabaseSession;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->make('flarum')->booted(function () {
            $config = $this->app->make('config');
            $flarumConfig = $this->app->make('flarum.config');
            $sessionConfig = $flarumConfig['session'] ?? [];

            if (is_array($sessionConfig)) {
                $current = $config->get('session', []);
                $config->set('session', array_merge($current, $sessionConfig));
            }
        });
    }
}
