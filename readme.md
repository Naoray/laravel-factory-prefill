# laravel-factory-prefill

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/naoray/laravel-factory-prefill.svg?style=flat-square)](https://packagist.org/packages/naoray/laravel-factory-prefill)

Factories are a great concept and I really love to use them for testing purposes, but it sucks to write every needed column name and associated faker methods by hand. This package aims to make the process less painfull by providing a command to prefill your factories!

After creating a factory with the artisan cli you end up having something like this:
```php
<?php

use Faker\Generator as Faker;

$factory->define(Model::class, function (Faker $faker) {
    return [
        //
    ];
});

```

With `laravel-factory-prefill` you can just skip the previous command call and instead execute `php artisan factory:prefill Habit`.

![factory:prefill](https://user-images.githubusercontent.com/10154100/48951760-0f64a680-ef40-11e8-84dc-d23183596178.gif)

## Install
`composer require naoray/laravel-factory-prefill`

## Usage
After running `php artisan migrate` you are good to go. If you want the `factory:prefill` command to notice the model relations, you should implement the methods first!

`php artisan factory:prefill model_name`

## Testing
Was not added yet.

Run the tests with:

``` bash
vendor/bin/phpunit
```

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security
If you discover any security-related issues, please email krishan.koenig@googlemail.com instead of using the issue tracker.

## License
The MIT License (MIT). Please see [License File](/LICENSE.md) for more information.