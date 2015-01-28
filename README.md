# Laravel 4.1 and 4.2 Route Caching

This package allows you to cache routes in Laravel 4.1 and 4.2.

[![Build Status](https://travis-ci.org/MaartenStaa/laravel-41-route-caching.svg)][1]
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/MaartenStaa/laravel-41-route-caching/badges/quality-score.png?b=master)][2]
[![Code Coverage](https://scrutinizer-ci.com/g/MaartenStaa/laravel-41-route-caching/badges/coverage.png?b=master)][3]

## Installation

Using [Composer](http://getcomposer.org/), add the package to your `require` section.

```json
{
	"require": {
		"maartenstaa/laravel-41-route-caching": "dev-master"
	}
}
```

Run `composer update`. Next, open up your `app/config/app.php` configuration file
and add the service provider at the end of the providers section:

```php
return array(
	// ...

	'providers' => array(
		// ...

		'MaartenStaa\Routing\RoutingServiceProvider',

	),

);
```

## Usage

In your `app/routes.php` file, or any other file you use that defines routes, wrap
the definition of your routes in a call to `Route::cache` as follows:

```php
Route::cache(__FILE__, function() {
	// Define your routes here.
});
```

This package will save the routes defined in the closure, and write them to your
cache. On any subsequent requests, it will figure out the closure will not have
to be executed, and it will load the routes from your cache instead. Since you're
passing it the name of the file that defines the routes (`__FILE__`), the script
will automatically detect when the file has been modified. In other words, you do
not need to clear your cache after adding a new route.


## Why?

Through profiling, I found that defining many routes (in my case 100+) took a
significant time on each request - time that would have been better spent preparing
the response for the user.

Caching these routes significantly reduces overhead.

## Limitations

You cannot use this package to serialize routes using a closure, such as this:

```php
Route::get('/', function () {
	return 'Hello, world!';
});
```

You can only use it to serialize routes to a controller. If your `app/routes.php`
file has both, you can of course put all controller routes in a `cache` call,
and any routes that use closures outside of it.

## Contributing

### Coding standard

All code is to follow the [PSR-2][4] coding standard.

### Unit tests

If you find a bug, feel free to send a pull request to fix it, but make sure to
always include a regression test.

[1]: https://travis-ci.org/MaartenStaa/laravel-41-route-caching
[2]: https://scrutinizer-ci.com/g/MaartenStaa/laravel-41-route-caching/?branch=master
[3]: https://scrutinizer-ci.com/g/MaartenStaa/laravel-41-route-caching/?branch=master
[4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
