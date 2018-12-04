<?php

namespace Naoray\LaravelFactoryPrefill;

use Faker\Provider\Base;
use Faker\Generator as Faker;

class TypeGuesser
{
    /**
     * @var \Faker\Generator
     */
    protected $generator;

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
     * @param string $name
     * @param int|null $size Length of field, if known
     * @return string
     */
    public function guess($name, $size = null)
    {
        $name = Base::toLower($name);

        if ($this->isBoolean($name)) {
            return 'boolean';
        }

        if ($this->isDateTime($name)) {
            return 'dateTime';
        }

        switch (str_replace('_', '', $name)) {
            case 'firstname':
                return 'firstName';
            case 'lastname':
                return 'lastName';
            case 'username':
            case 'login':
                return 'userName';
            case 'email':
            case 'emailaddress':
                return 'email';
            case 'phonenumber':
            case 'phone':
            case 'telephone':
            case 'telnumber':
                return 'phoneNumber';
            case 'address':
                return 'address';
            case 'city':
            case 'town':
                return 'city';
            case 'streetaddress':
                return 'streetAddress';
            case 'postcode':
            case 'zipcode':
                return 'postcode';
            case 'state':
                return 'state';
            case 'county':
                return $this->predictCountyType();
            case 'country':
                return $this->predictCountryType($size);
            case 'locale':
                return 'locale';
            case 'currency':
            case 'currencycode':
                return 'currencyCode';
            case 'url':
            case 'website':
                return 'url';
            case 'company':
            case 'companyname':
            case 'employer':
                return 'company';
            case 'title':
                return $this->predictTitleType($size);
            case 'body':
            case 'summary':
            case 'article':
            case 'description':
                return 'text';
            default:
                return 'word';
        }
    }

    /**
     * Checks if name matches boolean pattern.
     *
     * @param string $name
     * @return boolean
     */
    protected function isBoolean($name)
    {
        return preg_match('/^is[_A-Z]/', $name);
    }

    /**
     * Checks if name matches dateTime pattern.
     *
     * @param string $name
     * @return boolean
     */
    protected function isDateTime($name)
    {
        return preg_match('/(_a|A)t$/', $name);
    }

    /**
     * Predicts county type by locale.
     *
     * @return void
     */
    protected function predictCountyType()
    {
        if ($this->generator->locale == 'en_US') {
            return "sprintf('%s County', \$faker->city)";
        }

        return 'state';
    }

    /**
     * Predicts country code based on $size.
     *
     * @param int $size
     * @return void
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
     * @return void
     */
    protected function predictTitleType($size)
    {
        if ($size !== null && $size <= 10) {
            return 'title';
        }

        return 'sentence';
    }
}
