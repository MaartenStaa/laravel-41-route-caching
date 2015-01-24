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
     * @param string  $filename
     * @param Closure $callback
     * @param int     $cacheMinutes
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
    }
}
