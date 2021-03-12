<?php

namespace NassFloPetr\ExchangeRateGrabber\Observers;

use NassFloPetr\ExchangeRateGrabber\Model\ExchangeRate;

interface ExchangeRateObserver
{
    public function exchangeRateCreated(ExchangeRate $exchangeRate): void;

    public function exchangeRateUpdated(ExchangeRate $preExchangeRate, ExchangeRate $latestExchangeRate): void;

    public function exchangeRateChanged(ExchangeRate $preExchangeRate, ExchangeRate $latestExchangeRate): void;
}