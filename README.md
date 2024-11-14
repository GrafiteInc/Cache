![Grafite Cache](GrafiteCache-banner.png)

**Cache** - SQLite based cache driver with some fancy extras like tagging and encryption etc.

[![Build Status](https://github.com/GrafiteInc/Cache/workflows/PHP%20Package%20Tests/badge.svg?branch=main)](https://github.com/GrafiteInc/Cache/actions?query=workflow%3A%22PHP+Package+Tests%22)
[![Maintainability](https://api.codeclimate.com/v1/badges/a90e41bd64d41508ef0e/maintainability)](https://codeclimate.com/github/GrafiteInc/Cache/maintainability)
[![Packagist](https://img.shields.io/packagist/dt/grafite/cache.svg)](https://packagist.org/packages/grafite/cache)
[![license](https://img.shields.io/github/license/mashape/apistatus.svg)](https://packagist.org/packages/grafite/cache)

The Cache package an SQLite based cache driver with some fancy extras like tagging and encryption.

##### Author(s):
* [Matt Lantz](https://github.com/mlantz) ([@mattylantz](http://twitter.com/mattylantz), mattlantz at gmail dot com)

## Requirements

1. PHP 8.2+

## Compatibility and Support

| Laravel Version | Package Tag | Supported |
|-----------------|-------------|-----------|
| ^11.x | 1.x | yes |

### Installation

Start a new Laravel project:
```php
composer create-project laravel/laravel your-project-name
```

Then run the following to add Support
```php
composer require "grafite/cache"
```

Append to the `cache.php` config file in the stores array:
```php
'sqlite' => [
    'driver' => 'sqlite',
    'table' => 'cache',
    'database' => env('CACHE_DATABASE', database_path('cache.sqlite')),
    'prefix' => '',
],
```

## Documentation

[https://docs.grafite.ca/utilities/cache](https://docs.grafite.ca/utilities/cache)

## License
Support is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

### Bug Reporting and Feature Requests
Please add as many details as possible regarding submission of issues and feature requests

### Disclaimer
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
