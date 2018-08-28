<?php
/**
 * Copyright 2018 xooxx.dev@gmail.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Xooxx\Laravel\Access;

use Xooxx\Laravel\Access\Facades\Gate as GateFacade;
use Illuminate\Support\ServiceProvider;
use Xooxx\Laravel\Access\Contracts\Gate as GateContract;

abstract class GateServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [];

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton(GateContract::class, function ($app) {
            return new Gate($app, function () use ($app) {
                /**@var \Illuminate\Auth\Guard $auth */
                $auth = $app['auth'];
                
                if(config('auth.multi.tenant', false)){
                    return $auth->tenant()->getUser();
                }
                
                return $auth->getUser();
            });
        });
    }

    /**
     * Register the application's policies.
     *
     * @return void
     */
    public function registerPolicies()
    {
        foreach ($this->policies as $key => $value) {
            GateFacade::policy($key, $value);
        }
    }

    /**
     * Register any application authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
