<?php

/*
 *
 * Discuz & Tencent Cloud
 * This is NOT a freeware, use is subject to license terms
 *
 */

namespace Discuz\Socialite;

use Discuz\Contracts\Socialite\Factory;
use Illuminate\Support\ServiceProvider;

class SocialiteServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Factory::class, function ($app) {
            return new SocialiteManage($app);
        });
    }

    public function provides()
    {
        return [Factory::class];
    }
}
