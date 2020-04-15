# laravel-factory-prefill

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/naoray/laravel-factory-prefill.svg?style=flat-square)](https://packagist.org/packages/naoray/laravel-factory-prefill)
[![Build Status](https://travis-ci.org/Naoray/laravel-factory-prefill.svg?branch=master)](https://travis-ci.org/Naoray/laravel-factory-prefill)

Factories are a great concept and I really love to use them for testing purposes, but it sucks to write every needed column name and associated faker methods by hand. This package aims to make the process less painful by providing a command to prefill your factories!

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

![factory:prefill](https://user-images.githubusercontent.com/10154100/48952171-864e6f00-ef41-11e8-9e0d-a3c6ad332b76.gif)

## Install
`composer require naoray/laravel-factory-prefill --dev`

## Usage
After running `php artisan migrate` you are good to go. If you want the `factory:prefill` command to notice the model relations, you should implement the methods first!

*Tip: If you also want the realtionships to be loaded automatically, you have to define the methods in the models.*

### Fill all Factories
To generate factories for all models run

`php artisan factory:all`

#### Models in different directories
To prefill factories from models outside of the `app/` directory just add the `-P` flag and provide the path.

`php artisan factory:all --path=Some/Other/Path`

you can also append the `--realpath` option to indicate that the given path is a pre-resolved absolut path.

### Fill single Factory
To fill a single factory you can either run `php artisan factory:prefill model_name` or `php artisan factory:all model_name`.

#### Models with different namespace
To prefill factories from models outside of the `App/` namespace just add the `-O` flag and provide the full path in the model name.

`php artisan factory:prefill "Some\Other\Namespace\ModelName" -O`

### Nullable columns
By default `nullable` columns are ignored. If you want to also add `nullable` columns to your factory includ the flag `-N` or `--allow-nullable`.

`php artisan factory:prefill ModelName -N`
or
`php artisan factory:all -N`


## Testing
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
