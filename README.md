# Guzzle wrapper for OpenAgenda-API

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![codecov](https://codecov.io/gh/Erwane/openagenda-wrapper-guzzle/branch/1.x/graph/badge.svg?token=AMUOBQOUH1)](https://codecov.io/gh/Erwane/openagenda-wrapper-guzzle)
[![Build Status](https://github.com/Erwane/openagenda-wrapper-guzzle/actions/workflows/ci.yml/badge.svg?branch=1.x)](https://github.com/Erwane/openagenda-wrapper-guzzle/actions)
[![Packagist Downloads](https://img.shields.io/packagist/dt/Erwane/openagenda-wrapper-guzzle)](https://packagist.org/packages/Erwane/openagenda-wrapper-guzzle)
[![Packagist Version](https://img.shields.io/packagist/v/Erwane/openagenda-wrapper-guzzle)](https://packagist.org/packages/Erwane/openagenda-wrapper-guzzle)

Guzzle wrapper for [erwane/openagenda-api](https://github.com/Erwane/openagenda-api) package.

## Version map

| version | OpenAgenda-API Package | PHP min |
|---------|------------------------|---------|
| ^1.0    | 3.0.*                  | PHP 7.2 |
| ^2.0    | ^3.1                   | PHP 8.0 |

```php
use OpenAgenda\OpenAgenda;
use OpenAgenda\Wrapper\GuzzleWrapper

// PSR-18 Http client.
$guzzleOptions = ['timeout' => 2.0];
$wrapper = new GuzzleWrapper($guzzleOptions);

// PSR-16 Simple cache. Optional
$cache = new Psr16Cache();

// Create the OpenAgenda client. The public key is required for reading data (GET)
// The private key is optional and only needed for writing data (POST, PUT, DELETE)
$oa = new OpenAgenda([
    'public_key' => 'my public key', // Required
    'secret_key' => 'my secret key', // Optional, only for create/update/delete
    'wrapper' => $wrapper, // Required
    'cache' => $cache, // Optional
    'defaultLang' => 'fr', // Optional
]);
```

Check [OpenAgenda API lib](https://github.com/Erwane/openagenda-api/blob/3.x/README.md) for details.
