<?php namespace MaartenStaa\Routing;

/**
 * Copyright (c) 2015 by Maarten Staa.
 *
 * Some rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *
 *     * Redistributions in binary form must reproduce the above
 *       copyright notice, this list of conditions and the following
 *       disclaimer in the documentation and/or other materials provided
 *       with the distribution.
 *
 *     * The names of the contributors may not be used to endorse or
 *       promote products derived from this software without specific
 *       prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

use Closure;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router as LaravelRouter;

class Router extends LaravelRouter
{
    /**
     * Create a new Router instance.
     *
     * @param \Illuminate\Events\Dispatcher        $events
     * @param \Illuminate\Container\Container|null $container
     */
    public function __construct(Dispatcher $events, Container $container = null)
    {
        parent::__construct($events, $container);

        $this->routes = new RouteCollection;
    }

    /**
     * Indicate that the routes that are defined in the given callback
     * should be cached.
     *
     * @param  string  $filename
     * @param  Closure $callback
     * @param  int     $cacheMinutes
     * @return string
     */
    public function cache($filename, Closure $callback, $cacheMinutes = 1440)
    {
        $cacher = $this->container['cache'];
        $cacheKey = 'routes.cache.v1.'.md5($filename).filemtime($filename);

        // Check if the current route group is cached.
        if (($cache = $cacher->get($cacheKey)) !== null) {
            $this->routes->restoreRouteCache($cache);
        } else {
            // Back up current RouteCollection contents.
            $this->routes->saveRouteCollection();

            // Call closure to define routes that should be cached.
            $callback();

            // Put routes in cache.
            $cache = $this->routes->getCacheableRoutes();
            $cacher->put($cacheKey, $cache, $cacheMinutes);

            // And restore the routes that shouldn't be cached.
            $this->routes->restoreRouteCollection();
        }

        return $cacheKey;
    }

    /**
     * Determine if the action is routing to a controller.
     *
     * @param  array  $action
     * @return bool
     */
    public function routingToController($action)
    {
        return parent::routingToController($action);
    }

    /**
     * Add a controller based route action to the action array.
     *
     * @param  array|string  $action
     * @return array
     */
    protected function getControllerAction($action)
    {
        if (is_string($action) === true) {
            $action = array('uses' => $action);
        }

        // Here we'll get an instance of this controller dispatcher and hand it off to
        // the Closure so it will be used to resolve the class instances out of our
        // IoC container instance and call the appropriate methods on the class.
        if (count($this->groupStack) > 0) {
            $action['uses'] = $this->prependGroupUses($action['uses']);
        }

        // Here we'll get an instance of this controller dispatcher and hand it off to
        // the Closure so it will be used to resolve the class instances out of our
        // IoC container instance and call the appropriate methods on the class.
        $action['controller'] = $action['uses'];

        $closure = $action['uses'];

        return array_set($action, 'uses', $closure);
    }

    /**
     * Replace the string action in the given array with a Closure to call.
     *
     * @param  array $action
     * @return array
     */
    public function makeControllerActionClosure(array $action)
    {
        $closure = $this->getClassClosure($action['uses']);

        return array_set($action, 'uses', $closure);
    }

    /**
     * Create a new Route object.
     *
     * @param  array|string              $methods
     * @param  string                    $uri
     * @param  mixed                     $action
     * @return \Illuminate\Routing\Route
     */
    protected function newRoute($methods, $uri, $action)
    {
        return new Route($methods, $uri, $action);
    }
}
