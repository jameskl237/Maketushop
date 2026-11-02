<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.cinetpay.country' => 'CM']);
    }

    public function test_local_cameroon_number_gets_237(): void
    {
        $this->assertSame('+237690000000', PhoneNumber::toE164('690000000'));
        $this->assertSame('+237690000000', PhoneNumber::toE164('6 90 00 00 00'));
    }

    public function test_number_stored_without_plus_is_not_double_prefixed(): void
    {
        $this->assertSame('+237690000000', PhoneNumber::toE164('237690000000'));
    }

    public function test_international_formats_are_kept(): void
    {
        $this->assertSame('+237690000000', PhoneNumber::toE164('+237 690 00 00 00'));
        $this->assertSame('+237690000000', PhoneNumber::toE164('00237690000000'));
        $this->assertSame('+2250707000001', PhoneNumber::toE164('+2250707000001'));
    }

    public function test_empty_returns_empty(): void
    {
        $this->assertSame('', PhoneNumber::toE164(''));
        $this->assertSame('', PhoneNumber::toE164(null));
    }

    public function test_cameroon_mobile_validation(): void
    {
        $this->assertTrue(PhoneNumber::isValidMobile('+237690000000'));
        $this->assertFalse(PhoneNumber::isValidMobile('+23769000000'));   // 8 chiffres
        $this->assertFalse(PhoneNumber::isValidMobile('+237222000000'));  // fixe
        $this->assertFalse(PhoneNumber::isValidMobile('+2252376900000')); // ancien bug +225
    }
}
