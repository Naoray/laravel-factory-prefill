<?php

namespace Naoray\LaravelFactoryPrefill\Tests;

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

    /** @test */
    public function it_can_guess_boolean_values()
    {
        $this->assertEquals('boolean', $this->typeGuesser->guess('is_verified'));
        $this->assertEquals('boolean', $this->typeGuesser->guess('isVerified'));
        $this->assertEquals('boolean', $this->typeGuesser->guess('has_been_verified'));
        $this->assertEquals('boolean', $this->typeGuesser->guess('hasBeenVerified'));
    }

    /** @test */
    public function it_can_guess_date_time_values()
    {
        $this->assertEquals('dateTime', $this->typeGuesser->guess('done_at'));
        $this->assertEquals('dateTime', $this->typeGuesser->guess('doneAt'));
    }

    /** @test */
    public function it_can_guess_name_values()
    {
        $this->assertEquals('name', $this->typeGuesser->guess('name'));
    }

    /** @test */
    public function it_can_guess_first_name_values()
    {
        $this->assertEquals('firstName', $this->typeGuesser->guess('first_name'));
        $this->assertEquals('firstName', $this->typeGuesser->guess('firstname'));
    }

    /** @test */
    public function it_can_guess_last_name_values()
    {
        $this->assertEquals('lastName', $this->typeGuesser->guess('last_name'));
        $this->assertEquals('lastName', $this->typeGuesser->guess('lastname'));
    }

    /** @test */
    public function it_can_guess_user_name_values()
    {
        $this->assertEquals('userName', $this->typeGuesser->guess('username'));
        $this->assertEquals('userName', $this->typeGuesser->guess('user_name'));
        $this->assertEquals('userName', $this->typeGuesser->guess('login'));
    }

    /** @test */
    public function it_can_guess_email_values()
    {
        $this->assertEquals('email', $this->typeGuesser->guess('email'));
        $this->assertEquals('email', $this->typeGuesser->guess('emailaddress'));
        $this->assertEquals('email', $this->typeGuesser->guess('email_address'));
    }

    /** @test */
    public function it_can_guess_phone_number_values()
    {
        $this->assertEquals('phoneNumber', $this->typeGuesser->guess('phonenumber'));
        $this->assertEquals('phoneNumber', $this->typeGuesser->guess('phone'));
        $this->assertEquals('phoneNumber', $this->typeGuesser->guess('telephone'));
        $this->assertEquals('phoneNumber', $this->typeGuesser->guess('telnumber'));
    }

    /** @test */
    public function it_can_guess_address_values()
    {
        $this->assertEquals('address', $this->typeGuesser->guess('address'));
    }

    /** @test */
    public function it_can_guess_city_values()
    {
        $this->assertEquals('city', $this->typeGuesser->guess('city'));
        $this->assertEquals('city', $this->typeGuesser->guess('town'));
    }

    /** @test */
    public function it_can_guess_street_address_values()
    {
        $this->assertEquals('streetAddress', $this->typeGuesser->guess('street_address'));
        $this->assertEquals('streetAddress', $this->typeGuesser->guess('streetAddress'));
    }

    /** @test */
    public function it_can_guess_postcode_values()
    {
        $this->assertEquals('postcode', $this->typeGuesser->guess('postcode'));
        $this->assertEquals('postcode', $this->typeGuesser->guess('zipcode'));
    }

    /** @test */
    public function it_can_guess_state_values()
    {
        $this->assertEquals('state', $this->typeGuesser->guess('state'));
        $this->assertEquals('state', $this->typeGuesser->guess('county'));
    }

    /** @test */
    public function it_can_guess_country_values()
    {
        $this->assertEquals('countryCode', $this->typeGuesser->guess('country', 2));
        $this->assertEquals('countryISOAlpha3', $this->typeGuesser->guess('country', 3));
        $this->assertEquals('country', $this->typeGuesser->guess('country'));
    }

    /** @test */
    public function it_can_guess_locale_values()
    {
        $this->assertEquals('locale', $this->typeGuesser->guess('country', 5));
        $this->assertEquals('locale', $this->typeGuesser->guess('country', 6));
        $this->assertEquals('locale', $this->typeGuesser->guess('locale'));
    }

    /** @test */
    public function it_can_guess_currency_code_values()
    {
        $this->assertEquals('currencyCode', $this->typeGuesser->guess('currency'));
        $this->assertEquals('currencyCode', $this->typeGuesser->guess('currencycode'));
        $this->assertEquals('currencyCode', $this->typeGuesser->guess('currency_code'));
    }

    /** @test */
    public function it_can_guess_url_values()
    {
        $this->assertEquals('url', $this->typeGuesser->guess('website'));
        $this->assertEquals('url', $this->typeGuesser->guess('url'));
    }

    /** @test */
    public function it_can_guess_company_values()
    {
        $this->assertEquals('company', $this->typeGuesser->guess('company'));
        $this->assertEquals('company', $this->typeGuesser->guess('companyname'));
        $this->assertEquals('company', $this->typeGuesser->guess('company_name'));
        $this->assertEquals('company', $this->typeGuesser->guess('employer'));
    }

    /** @test */
    public function it_can_guess_title_values()
    {
        $this->assertEquals('title', $this->typeGuesser->guess('title', 10));
        $this->assertEquals('title', $this->typeGuesser->guess('title'));
    }

    /** @test */
    public function it_can_guess_sentence_values()
    {
        $this->assertEquals('sentence', $this->typeGuesser->guess('title', 15));
    }

    /** @test */
    public function it_can_guess_text_values()
    {
        $this->assertEquals('text', $this->typeGuesser->guess('body'));
        $this->assertEquals('text', $this->typeGuesser->guess('summary'));
        $this->assertEquals('text', $this->typeGuesser->guess('article'));
        $this->assertEquals('text', $this->typeGuesser->guess('description'));
    }

    /** @test */
    public function it_can_guess_random_number_values()
    {
        $this->assertEquals('randomNumber', $this->typeGuesser->guess('integer'));
        $this->assertEquals('randomNumber(10)', $this->typeGuesser->guess('integer', 10));
    }

    /** @test */
    public function it_can_guess_password_values()
    {
        $this->assertEquals('bcrypt($faker->word(10))', $this->typeGuesser->guess('password', 10));
    }

    /** @test */
    public function it_returns_word_as_default_value()
    {
        $this->assertEquals('word', $this->typeGuesser->guess('not_guessable'));
    }
}
