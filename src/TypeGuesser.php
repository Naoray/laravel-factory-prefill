<?php

namespace Naoray\LaravelFactoryPrefill;

use Illuminate\Support\Str;
use Doctrine\DBAL\Types\Type;
use Faker\Generator as Faker;
use InvalidArgumentException;
use Doctrine\DBAL\Types\Types;

class TypeGuesser
{
    /**
     * @var \Faker\Generator
     */
    protected $generator;

    /**
     * @var string
     */
    protected static $default = 'word';

    /**
     * Create a new TypeGuesser instance.
     *
     * @param \Faker\Generator $generator
     */
    public function __construct(Faker $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @param string                   $name
     * @param Doctrine\DBAL\Types\Type $type
     * @param int|null                 $size Length of field, if known
     *
     * @return string
     */
    public function guess($name, Type $type, $size = null)
    {
        $name = str_replace('_', '', Str::lower($name));

        if (!$size && $this->hasNativeResolverFor($name)) {
            return $name;
        }

        if (self::$default !== $typeNameGuess = $this->guessBasedOnName($name, $size)) {
            return $typeNameGuess;
        }

        return $this->guessBasedOnType($type, $size);
    }

    /**
     * Get type guess.
     *
     * @param string   $name
     * @param int|null $size
     *
     * @return string
     */
    private function guessBasedOnName($name, $size = null)
    {
        switch ($name) {
            case 'login':
                return 'userName';
            case 'emailaddress':
                return 'email';
            case 'phone':
            case 'telephone':
            case 'telnumber':
                return 'phoneNumber';
            case 'town':
                return 'city';
            case 'zipcode':
                return 'postcode';
            case 'county':
                return $this->predictCountyType();
            case 'country':
                return $this->predictCountryType($size);
            case 'currency':
                return 'currencyCode';
            case 'website':
                return 'url';
            case 'companyname':
            case 'employer':
                return 'company';
            case 'title':
                return $this->predictTitleType($size);
            default:
                return self::$default;
        }
    }

    /**
     * Check if faker instance has a native resolver for the given property.
     *
     * @param string $property
     *
     * @return bool
     */
    protected function hasNativeResolverFor($property)
    {
        try {
            $this->generator->getFormatter($property);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * Try to guess the right faker method for the given type.
     *
     * @param Type     $type
     * @param int|null $size
     *
     * @return string
     */
    protected function guessBasedOnType(Type $type, $size)
    {
        $typeName = $type->getName();

        switch ($typeName) {
            case Types::BOOLEAN:
                return 'boolean';
            case Types::BIGINT:
            case Types::INTEGER:
            case Types::SMALLINT:
                return 'randomNumber' . ($size ? "($size)" : '');
            case Types::DATE_MUTABLE:
            case Types::DATE_IMMUTABLE:
                return 'date';
            case Types::DATETIME_MUTABLE:
            case Types::DATETIME_IMMUTABLE:
                return 'dateTime';
            case Types::DECIMAL:
            case Types::FLOAT:
                return 'randomFloat' . ($size ? "($size)" : '');
            case Types::TEXT:
                return 'text';
            case Types::TIME_MUTABLE:
            case Types::TIME_IMMUTABLE:
                return 'time';
            default:
                return self::$default;
        }
    }

    /**
     * Predicts county type by locale.
     */
    protected function predictCountyType()
    {
        if ('en_US' == $this->generator->locale) {
            return "sprintf('%s County', \$faker->city)";
        }

        return 'state';
    }

    /**
     * Predicts country code based on $size.
     *
     * @param int $size
     */
    protected function predictCountryType($size)
    {
        switch ($size) {
            case 2:
                return 'countryCode';
            case 3:
                return 'countryISOAlpha3';
            case 5:
            case 6:
                return 'locale';
        }

        return 'country';
    }

    /**
     * Predicts type of title by $size.
     *
     * @param int $size
     */
    protected function predictTitleType($size)
    {
        if (null === $size || $size <= 10) {
            return 'title';
        }

        return 'sentence';
    }
}
