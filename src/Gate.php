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

use Xooxx\JsonApi\Server\Actions\Exceptions\ForbiddenException;
use Illuminate\Auth\UserInterface;
use Illuminate\Container\Container;
use Illuminate\Support\Str;
use Xooxx\Laravel\Access\Contracts\Gate as GateContract;


class Gate implements GateContract
{

    use HandlesAuthorization;

    /**
     * The container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * All of the defined policies.
     *
     * @var array
     */
    protected $policies = [];


    /**
     * The user resolver callable.
     *
     * @var callable
     */
    protected $userResolver;

    /**
     * Create a new gate instance.
     *
     * @param  \Illuminate\Container\Container $container
     * @param callable $userResolver
     * @param  array $policies
     */
    public function __construct(Container $container,  callable $userResolver, array $policies = [])
    {
        $this->policies = $policies;
        $this->container = $container;
        $this->userResolver = $userResolver;
    }


    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  string $ability
     * @param  array|mixed $arguments
     * @return bool
     */
    public function allows($ability, $arguments = [])
    {
        return $this->check($ability, $arguments);
    }

    /**
     * Determine if the given ability should be denied for the current user.
     *
     * @param  string $ability
     * @param  array|mixed $arguments
     * @return bool
     */
    public function denies($ability, $arguments = [])
    {
        return !$this->allows($ability, $arguments);
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  string $ability
     * @param  array|mixed $arguments
     *
     * @throws ForbiddenException
     */
    public function authorize($ability, $arguments = [])
    {
        if(! $this->check($ability, $arguments)){
            $this->deny();
        }
    }

    /**
     * Determine if all of the given abilities should be granted for the current user.
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function check($abilities, $arguments = [])
    {;
        //If no user, then is not allowed
        if (! $user = $this->resolveUser()) {
            return false;
        }

        //Arr::wrap
         if($arguments == null){
            $arguments = [];
        }else if (!is_array($arguments)){
            $arguments = [$arguments];
        }

        if($abilities == null){
            $abilities = [];
        }else if (!is_array($abilities)){
            $abilities = [$abilities];
        }


        //Check if not all abilities are allowed for the user
        return collect($abilities)->reject(function ($ability) use ($arguments, $user) {
            return $this->callAuthCallback($user, $ability, $arguments);
        })->count() == 0;
    }

    /**
     * Resolve and call the appropriate authorization callback.
     *
     * @param  UserInterface  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return bool
     */
    protected function callAuthCallback($user, $ability, array $arguments)
    {
        $callback = $this->resolveAuthCallback($user, $ability, $arguments);
        return $callback($user, ...$arguments);
    }

    /**
     * Resolve the callable for the given ability and arguments.
     *
     * @param  UserInterface  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return callable
     */
    protected function resolveAuthCallback($user, $ability, array $arguments)
    {
        if (isset($arguments[0]) &&
            ! is_null($policy = $this->getPolicyFor($arguments[0])) &&
            $callback = $this->resolvePolicyCallback($user, $ability, $arguments, $policy)) {
            return $callback;
        }
        return function () {
            return false;
        };
    }

    /**
     * Resolve the callback for a policy check.
     *
     * @param  UserInterface  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @param  mixed  $policy
     * @return bool|callable
     */
    protected function resolvePolicyCallback($user, $ability, array $arguments, $policy)
    {
        if (! is_callable([$policy, $this->formatAbilityToMethod($ability)])) {
            return false;
        }
        return function () use ($user, $ability, $arguments, $policy) {

            $ability = $this->formatAbilityToMethod($ability);
            // If this first argument is a string, that means they are passing a class name
            // to the policy. We will remove the first argument from this argument array
            // because this policy already knows what type of models it can authorize.
            if (isset($arguments[0]) && is_string($arguments[0])) {
                array_shift($arguments);
            }
            return is_callable([$policy, $ability])
                ? $policy->{$ability}($user, ...$arguments)
                : false;
        };
    }

    /**
     * Format the policy ability into a method name.
     *
     * @param  string  $ability
     * @return string
     */
    protected function formatAbilityToMethod($ability)
    {
        return strpos($ability, '-') !== false ? Str::camel($ability) : $ability;
    }

    /**
     * Get a policy instance for a given class.
     *
     * @param  object|string  $class
     * @return mixed
     */
    public function getPolicyFor($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        if (! is_string($class)) {
            return null;
        }
        if (isset($this->policies[$class])) {
            return $this->resolvePolicy($this->policies[$class]);
        }
        foreach ($this->policies as $expected => $policy) {
            if (is_subclass_of($class, $expected)) {
                return $this->resolvePolicy($policy);
            }
        }
    }

    /**
     * Build a policy class instance of the given type.
     *
     * @param  object|string  $class
     * @return mixed
     */
    public function resolvePolicy($class)
    {
        return $this->container->make($class);
    }

    /**
     * Resolve the user from the user resolver.
     *
     * @return mixed
     */
    protected function resolveUser()
    {
        return call_user_func($this->userResolver);
    }

    /**
     * Define a policy class for a given class type.
     *
     * @param  string  $class
     * @param  string  $policy
     * @return $this
     */
    public function policy($class, $policy)
    {
        $this->policies[$class] = $policy;
        return $this;
    }

    /**
     * Get a gate instance for the given user.
     *
     * @param  UserInterface|mixed  $user
     * @return static
     */
    public function forUser($user)
    {
        $callback = function () use ($user) {
            return $user;
        };
        return new static(
            $this->container, $callback, $this->policies
        );
    }

    /**
     * Get all of the defined policies.
     *
     * @return array
     */
    public function policies()
    {
        return $this->policies;
    }

}