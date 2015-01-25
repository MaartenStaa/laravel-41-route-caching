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

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpKernel\Client;

class IntegrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        if ($this->app === null) {
            $this->refreshApplication();
        }
    }

    /**
     * Refresh the application instance.
     *
     * @return void
     */
    protected function refreshApplication()
    {
        $this->app = $this->createApplication();

        Facade::setFacadeApplication($this->app);

        $this->app['env'] = 'testing';

        $this->app['path.storage'] = __DIR__;

        $loader = $this->getMockBuilder('Illuminate\Config\LoaderInterface')
            ->setMethods(array('load', 'exists', 'getNamespaces', 'cascadePackage'))
            ->getMockForAbstractClass();

        $loader->method('load')
            ->will($this->returnValue(array()));

        $loader->method('exists')
            ->will($this->returnValue(true));

        $loader->method('getNamespaces')
            ->will($this->returnValue(array()));

        $loader->method('cascadePackage')
            ->will($this->returnValue(array()));

        $this->app['config'] = new Repository($loader, $this->app['env']);

        $this->app['cache'] = new CacheManager($this->app);
        $this->app['cache']->setDefaultDriver('array');

        $this->app['session'] = new SessionManager($this->app);
        $this->app['session']->setDefaultDriver('array');

        $this->app->boot();
    }

    /**
     * Creates the application.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    protected function createApplication()
    {
        return new Application;
    }

    /**
     * Create a router.
     *
     * @return Router
     */
    protected function getRouter()
    {
        return new Router($this->app['events'], $this->app);
    }

    /**
     * Create a new HttpKernel client instance.
     *
     * @param  array  $server
     * @return \Symfony\Component\HttpKernel\Client
     */
    protected function createClient(array $server = array())
    {
        return new Client($this->app, $server);
    }

    public function testCacheRoutes()
    {
        $router = $this->getRouter();

        $key = $router->cache(
            __FILE__,
            function () use ($router) {
                $router->get('/', 'HomeController@actionIndex');
            }
        );

        $this->assertTrue($this->app->cache->has($key), 'Routes must be in cache');
        $this->assertEquals(1, $router->getRoutes()->count(), 'Routes must be in collection');

        $cachedRoutes = unserialize($this->app->cache->get($key));
        $this->assertArrayHasKey('routes', $cachedRoutes);
        $this->assertArrayHasKey('GET', $cachedRoutes['routes']);
        $this->assertCount(1, $cachedRoutes['routes']['GET']);

        // Next request should not call the callback.
        $router = $this->getRouter();
        $router->cache(
            __FILE__,
            function () use ($router) {
                throw new Exception('This should not be called');
            }
        );
        $this->assertEquals(1, $router->getRoutes()->count(), 'Routes must be obtained from cache');
    }

    public function testAllMethodsWorks()
    {
        $methods = array('get', 'post', 'put', 'patch', 'delete');

        $router = $this->getRouter();

        $key = $router->cache(
            __FILE__,
            function () use ($router, $methods) {
                foreach ($methods as $method) {
                    $router->$method('/', 'HomeController@action' . ucfirst($method));
                }
            }
        );

        $this->assertTrue($this->app->cache->has($key), 'Routes must be in cache');
        $this->assertEquals(count($methods), $router->getRoutes()->count(), 'Routes must be in collection');

        $cachedRoutes = unserialize($this->app->cache->get($key));
        $this->assertArrayHasKey('routes', $cachedRoutes);

        foreach ($methods as $method) {
            $this->assertArrayHasKey(strtoupper($method), $cachedRoutes['routes']);
            $this->assertCount(1, $cachedRoutes['routes'][strtoupper($method)]);
        }

        // Next request should not call the callback.
        $router = $this->getRouter();
        $router->cache(
            __FILE__,
            function () use ($router) {
                throw new Exception('This should not be called');
            }
        );
        $this->assertEquals(count($methods), $router->getRoutes()->count(), 'Routes must be obtained from cache');
    }

    public function testControllerRouting()
    {
        $router = $this->getRouter();

        $controllerName = str_shuffle('abcdefghijklmnopqrstuvwxyz');

        // Create a controller class.
        eval('class ' . $controllerName . ' extends Illuminate\Routing\Controller { public function getHomePage() {} }');

        $key = $router->cache(
            __FILE__,
            function () use ($router, $controllerName) {
                $router->controller('/', $controllerName);
            }
        );

        $this->assertTrue($this->app->cache->has($key), 'Routes must be in cache');
        // 2 because controller adds missingMethod
        $this->assertEquals(2, $router->getRoutes()->count(), 'Routes must be in collection');

        // Next request should not call the callback.
        $router = $this->getRouter();
        $router->cache(
            __FILE__,
            function () use ($router) {
                throw new Exception('This should not be called');
            }
        );
        $this->assertEquals(2, $router->getRoutes()->count(), 'Routes must be obtained from cache');
    }

    public function testCanDispatchRequest()
    {
        // Create a controller class.
        $controllerName = str_shuffle('abcdefghijklmnopqrstuvwxyz');
        eval('class ' . $controllerName . ' extends Illuminate\Routing\Controller {
            public function getIndex() {
                return Illuminate\Support\Facades\Response::make(1);
            }
        }');

        // First, define a route.
        $router = $this->getRouter();
        $router->cache(
            __FILE__,
            function () use ($router, $controllerName) {
                $router->get('/', $controllerName . '@getIndex');
            }
        );

        // Create a new router, set it on the app, and simulate a request.
        $this->app['router'] = $this->getRouter();
        $this->app['router']->cache(
            __FILE__,
            function () use ($router) {
                throw new Exception('This should not be called');
            }
        );

        $client = $this->createClient();
        $client->request('get', '/');

        $response = $client->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals(1, $response->getContent());
    }

    public function testCanRouteToClosure()
    {
        // Create a new router, set it on the app, and simulate a request.
        $this->app['router'] = $this->getRouter();
        $this->app['router']->get(
            '/',
            function () {
                return 1;
            }
        );

        $client = $this->createClient();
        $client->request('get', '/');

        $response = $client->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals(1, $response->getContent());
    }
}
