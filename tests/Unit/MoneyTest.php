<?php

namespace Tests\Unit;

use App\Support\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_amounts_are_shown_in_cfa_francs_without_cents(): void
    {
        $nbsp = "\u{00A0}";

        $this->assertSame("150{$nbsp}000{$nbsp}FCFA", Money::format(150000));
        $this->assertSame("1{$nbsp}250{$nbsp}500{$nbsp}FCFA", Money::format('1250499.6'));
        $this->assertSame("0{$nbsp}FCFA", Money::format(null));
        $this->assertSame("45{$nbsp}300", Money::number(45300));
    }
}
