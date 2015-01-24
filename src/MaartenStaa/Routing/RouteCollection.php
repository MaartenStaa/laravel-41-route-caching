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

use Illuminate\Routing\RouteCollection as LaravelRouteCollection;

class RouteCollection extends LaravelRouteCollection
{
	/**
	 * A backup of the routes we had before clearing the collection in order to
	 * cache a part of the complete list of routes. The list of routes is put into
	 * the backup, new routes are defined and cached, and the backup is added back.
	 *
	 * @var array
	 */
    protected $backup;

    /**
     * Save the current collection of routes to the backup to start with an empty
     * collection to cache.
     */
    public function saveRouteCollection()
    {
        $this->backup = $this->getCacheableRouteContents();

        foreach (array_keys($this->backup) as $k) {
            $this->$k = array();
        }
    }

    /**
     * Restore the backed up routes.
     */
    public function restoreRouteCollection()
    {
        foreach ($this->backup as $k => $v) {
            if ($k == 'routes') {
                $this->$k = $this->mergeGroupedRoutes($v, $this->$k);
            } else {
                $this->$k = $this->mergeRoutes($v, $this->$k);
            }
        }
    }

    /**
     * Get the routes in a form that can be saved to the cache.
     *
     * @return string
     */
    public function getCacheableRoutes()
    {
        return serialize($this->getCacheableRouteContents());
    }

    /**
     * Get the data that should be sent to the cache.
     *
     * @return array
     */
    public function getCacheableRouteContents()
    {
        return array_except(get_object_vars($this), 'backup');
    }

    /**
     * Restore a set of cached routes into this collection.
     *
     * @param string $cache
     */
    public function restoreRouteCache($cache)
    {
        $cache = unserialize($cache);

        foreach ($cache as $k => $v) {
            if ($k == 'routes') {
                $this->$k = $this->mergeGroupedRoutes($this->$k, $v);
            } else {
                $this->$k = $this->mergeRoutes($this->$k, $v);
            }
        }
    }

    /**
     * Merge two array, each containing routes grouped by method.
     *
     * @param  array
     * @param  array
     * @return array
     */
    public function mergeGroupedRoutes(array $r1, array $r2)
    {
        $methods = array('GET', 'POST', 'HEAD', 'PATH', 'PUT');
        foreach ($methods as $method) {
            if (!isset($r2[$method])) {
                continue;
            }
            if (!isset($r1[$method])) {
                $r1[$method] = array();
            }

            $r1[$method] = $this->mergeRoutes($r1[$method], $r2[$method]);
        }

        return $r1;
    }

    /**
     * Merge two arrays, each containing routes.
     *
     * @param  array
     * @param  array
     * @return array
     */
    public function mergeRoutes(array $r1, array $r2)
    {
        foreach ($r2 as $uri => $route) {
            $r1[$uri] = $route;
        }

        return $r1;
    }
}
