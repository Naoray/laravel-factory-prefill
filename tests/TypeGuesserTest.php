<?php

namespace Naoray\LaravelFactoryPrefill\Tests;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Naoray\LaravelFactoryPrefill\TypeGuesser;

class TypeGuesserTest extends TestCase
{
    /**
     * @var TypeGuesser
     */
    protected $typeGuesser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->typeGuesser = resolve(TypeGuesser::class);
    }

    /**
     * Get type class for type string.
     *
     * @param string $type
     *
     * @return \Doctrine\DBAL\Types\Types
     */
    protected function getType($type = Types::STRING)
    {
        return Type::getType($type);
    }

    /** @test */
    public function it_can_guess_boolean_values_by_type()
    {
        $this->assertEquals('boolean', $this->typeGuesser->guess('is_verified', $this->getType(Types::BOOLEAN)));
    }

    /** @test */
    public function it_can_guess_random_integer_values_by_type()
    {
        $this->assertEquals('randomNumber', $this->typeGuesser->guess('integer', $this->getType(Types::INTEGER)));
        $this->assertEquals('randomNumber(10)', $this->typeGuesser->guess('integer', $this->getType(Types::INTEGER), 10));

        $this->assertEquals('randomNumber', $this->typeGuesser->guess('big_int', $this->getType(Types::BIGINT)));
        $this->assertEquals('randomNumber(10)', $this->typeGuesser->guess('big_int', $this->getType(Types::BIGINT), 10));

        $this->assertEquals('randomNumber', $this->typeGuesser->guess('small_int', $this->getType(Types::SMALLINT)));
        $this->assertEquals('randomNumber(10)', $this->typeGuesser->guess('small_int', $this->getType(Types::SMALLINT), 10));
    }

    /** @test */
    public function it_can_guess_random_decimal_values_by_type()
    {
        $this->assertEquals('randomFloat', $this->typeGuesser->guess('decimal_value', $this->getType(Types::DECIMAL)));
        $this->assertEquals('randomFloat(10)', $this->typeGuesser->guess('decimal_value', $this->getType(Types::DECIMAL), 10));
    }

    /** @test */
    public function it_can_guess_random_float_values_by_type()
    {
        $this->assertEquals('randomFloat', $this->typeGuesser->guess('float_value', $this->getType(Types::FLOAT)));
        $this->assertEquals('randomFloat(10)', $this->typeGuesser->guess('float_value', $this->getType(Types::FLOAT), 10));
    }

    /** @test */
    public function it_can_guess_date_time_values_by_type()
    {
        $this->assertEquals('dateTime', $this->typeGuesser->guess('done_at', $this->getType(Types::DATETIME_IMMUTABLE)));
        $this->assertEquals('date', $this->typeGuesser->guess('birthdate', $this->getType(Types::DATE_IMMUTABLE)));
        $this->assertEquals('time', $this->typeGuesser->guess('closing_at', $this->getType(Types::TIME_IMMUTABLE)));
    }

    /** @test */
    public function it_can_guess_text_values_by_type()
    {
        $this->assertEquals('text', $this->typeGuesser->guess('body', $this->getType(Types::TEXT)));
    }

    /** @test */
    public function it_can_guess_name_values()
    {
        $this->assertEquals('name', $this->typeGuesser->guess('name', $this->getType()));
    }

    /** @test */
    public function it_can_guess_first_name_values()
    {
        $this->assertEquals('firstname', $this->typeGuesser->guess('first_name', $this->getType()));
        $this->assertEquals('firstname', $this->typeGuesser->guess('firstname', $this->getType()));
    }

    /** @test */
    public function it_can_guess_last_name_values()
    {
        $this->assertEquals('lastname', $this->typeGuesser->guess('last_name', $this->getType()));
        $this->assertEquals('lastname', $this->typeGuesser->guess('lastname', $this->getType()));
    }

    /** @test */
    public function it_can_guess_user_name_values()
    {
        $this->assertEquals('username', $this->typeGuesser->guess('username', $this->getType()));
        $this->assertEquals('username', $this->typeGuesser->guess('user_name', $this->getType()));
        $this->assertEquals('userName', $this->typeGuesser->guess('login', $this->getType()));
    }

    /** @test */
    public function it_can_guess_email_values()
    {
        $this->assertEquals('email', $this->typeGuesser->guess('email', $this->getType()));
        $this->assertEquals('email', $this->typeGuesser->guess('emailaddress', $this->getType()));
        $this->assertEquals('email', $this->typeGuesser->guess('email_address', $this->getType()));
    }

    /** @test */
    public function it_can_guess_phone_number_values()
    {
        $this->assertEquals('phonenumber', $this->typeGuesser->guess('phonenumber', $this->getType()));
        $this->assertEquals('phoneNumber', $this->typeGuesser->guess('phone', $this->getType()));
        $this->assertEquals('phoneNumber', $this->typeGuesser->guess('telephone', $this->getType()));
        $this->assertEquals('phoneNumber', $this->typeGuesser->guess('telnumber', $this->getType()));
    }

    /** @test */
    public function it_can_guess_address_values()
    {
        $this->assertEquals('address', $this->typeGuesser->guess('address', $this->getType()));
    }

    /** @test */
    public function it_can_guess_city_values()
    {
        $this->assertEquals('city', $this->typeGuesser->guess('city', $this->getType()));
        $this->assertEquals('city', $this->typeGuesser->guess('town', $this->getType()));
    }

    /** @test */
    public function it_can_guess_street_address_values()
    {
        $this->assertEquals('streetaddress', $this->typeGuesser->guess('street_address', $this->getType()));
        $this->assertEquals('streetaddress', $this->typeGuesser->guess('streetAddress', $this->getType()));
    }

    /** @test */
    public function it_can_guess_postcode_values()
    {
        $this->assertEquals('postcode', $this->typeGuesser->guess('postcode', $this->getType()));
        $this->assertEquals('postcode', $this->typeGuesser->guess('zipcode', $this->getType()));
    }

    /** @test */
    public function it_can_guess_state_values()
    {
        $this->assertEquals('state', $this->typeGuesser->guess('state', $this->getType()));
        $this->assertEquals('state', $this->typeGuesser->guess('county', $this->getType()));
    }

    /** @test */
    public function it_can_guess_country_values()
    {
        $this->assertEquals('countryCode', $this->typeGuesser->guess('country', $this->getType(), 2));
        $this->assertEquals('countryISOAlpha3', $this->typeGuesser->guess('country', $this->getType(), 3));
        $this->assertEquals('country', $this->typeGuesser->guess('country', $this->getType()));
    }

    /** @test */
    public function it_can_guess_locale_values()
    {
        $this->assertEquals('locale', $this->typeGuesser->guess('country', $this->getType(), 5));
        $this->assertEquals('locale', $this->typeGuesser->guess('country', $this->getType(), 6));
        $this->assertEquals('locale', $this->typeGuesser->guess('locale', $this->getType()));
    }

    /** @test */
    public function it_can_guess_currency_code_values()
    {
        $this->assertEquals('currencyCode', $this->typeGuesser->guess('currency', $this->getType()));
        $this->assertEquals('currencycode', $this->typeGuesser->guess('currencycode', $this->getType()));
        $this->assertEquals('currencycode', $this->typeGuesser->guess('currency_code', $this->getType()));
    }

    /** @test */
    public function it_can_guess_url_values()
    {
        $this->assertEquals('url', $this->typeGuesser->guess('website', $this->getType()));
        $this->assertEquals('url', $this->typeGuesser->guess('url', $this->getType()));
    }

    /** @test */
    public function it_can_guess_company_values()
    {
        $this->assertEquals('company', $this->typeGuesser->guess('company', $this->getType()));
        $this->assertEquals('company', $this->typeGuesser->guess('companyname', $this->getType()));
        $this->assertEquals('company', $this->typeGuesser->guess('company_name', $this->getType()));
        $this->assertEquals('company', $this->typeGuesser->guess('employer', $this->getType()));
    }

    /** @test */
    public function it_can_guess_title_values()
    {
        $this->assertEquals('title', $this->typeGuesser->guess('title', $this->getType(), 10));
        $this->assertEquals('title', $this->typeGuesser->guess('title', $this->getType()));
    }

    /** @test */
    public function it_can_guess_sentence_values()
    {
        $this->assertEquals('sentence', $this->typeGuesser->guess('title', $this->getType(), 15));
    }

    /** @test */
    public function it_can_guess_password_values()
    {
        $this->assertEquals('password', $this->typeGuesser->guess('password', $this->getType()));
    }

    /** @test */
    public function it_returns_word_as_default_value()
    {
        $this->assertEquals('word', $this->typeGuesser->guess('not_guessable', $this->getType()));
    }

    /** @test */
    public function it_can_guess_properties_based_on_their_names()
    {
        $this->assertEquals('latitude', $this->typeGuesser->guess('latitude', $this->getType()));
    }
}
