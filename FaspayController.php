<?php

namespace App\Http\Controllers\Payment;

use Faspay\Credit\FaspayUserCredit;

class FaspayController extends FaspayUserCredit
{
    function __construct()
    {
        $this->setMerchantId(config('faspay.merchantid'));
        $this->setPass(config('faspay.password'));
    }
}
