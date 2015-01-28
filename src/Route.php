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

use Illuminate\Routing\Route as LaravelRoute;

class Route extends LaravelRoute
{
    /**
     * Whether routes should be compiled. Laravel version dependent.
     *
     * @var null|bool
     */
    public static $shouldCompileRoute = null;

    /**
     * Run the route action and return the response.
     *
     * @return mixed
     */
    public function run()
    {
        $app = app();

        if ($app['router']->routingToController($this->action) === true) {
            $this->action = $app['router']->makeControllerActionClosure($this->action);
        }

        return parent::run();
    }

    /**
     * Determines whether routes should be compiled. Caches the value at a class
     * level.
     *
     * @return bool
     */
    protected function shouldCompile()
    {
        if (static::$shouldCompileRoute === null) {
            // If the compiled variable exists, we should compile.
            static::$shouldCompileRoute = array_key_exists('compiled', get_object_vars($this));
        }

        return static::$shouldCompileRoute;
    }

    /**
     * Compile the route into a Symfony CompiledRoute instance.
     */
    protected function compileRoute()
    {
        if ($this->shouldCompile() === true && $this->compiled === null) {
            parent::compileRoute();
        }
    }

    /**
     * Set a regular expression requirement on the route.
     *
     * @param  array|string              $name
     * @param  string|null               $expression
     * @return \Illuminate\Routing\Route
     */
    public function where($name, $expression = null)
    {
        parent::where($name, $expression);

        // We now have enough information to compile the route.
        $this->compileRoute();

        return $this;
    }
}
