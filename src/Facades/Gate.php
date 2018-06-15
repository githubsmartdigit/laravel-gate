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

namespace Xooxx\Laravel\Access\Facades;
use Illuminate\Auth\UserInterface;
use Illuminate\Support\Facades\Facade;
use Xooxx\Laravel\Access\Contracts\Gate as GateContract;


/**
 * @method static GateContract policy(string $class, string $policy)
 * @method static bool allows(string $ability, array | mixed $arguments = [])
 * @method static bool denies(string $ability, array | mixed $arguments = [])
 * @method static bool check(iterable | string $abilities, array | mixed $arguments = [])
 * @method static void authorize(string $ability, array | mixed $arguments = [])
 * @method static mixed getPolicyFor(object | string $class)
 * @method static GateContract forUser(UserInterface | mixed $user)
 *
 * @see GateContract
 */
class Gate extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return GateContract::class;
    }
}