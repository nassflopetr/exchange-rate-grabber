<?php

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Model\ExchangeRate;

abstract class Grabber
{
    private ?array $baseCurrencyCodes;
    private ?array $destinationCurrencyCodes;

    public function __construct(?array $baseCurrencyCodes = null, ?array $destinationCurrencyCodes = null)
    {
        $this->baseCurrencyCodes = $baseCurrencyCodes;
        $this->destinationCurrencyCodes = $destinationCurrencyCodes;
    }

    public function __serialize(): array
    {
        return [
            'base_currency_codes' => $this->baseCurrencyCodes,
            'destination_currency_codes' => $this->destinationCurrencyCodes,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->__construct(
            $data['base_currency_codes'],
            $data['destination_currency_codes']
        );
    }

    abstract public function getExchangeRates(): array;

    protected function getExchangeRate(
        string $baseCurrencyCode,
        string $destinationCurrencyCode,
        float $buyRate,
        float $saleRate
    ): ?ExchangeRate
    {
        if (
            !\is_null($this->baseCurrencyCodes)
            && !empty($this->baseCurrencyCodes)
            && !\in_array($baseCurrencyCode, $this->baseCurrencyCodes)
        ) {
            return null;
        }

        if (
            !\is_null($this->destinationCurrencyCodes)
            && !empty($this->destinationCurrencyCodes)
            && !\in_array($destinationCurrencyCode, $this->destinationCurrencyCodes)
        ) {
            return null;
        }

        return new ExchangeRate(
            new static([$baseCurrencyCode], [$destinationCurrencyCode]),
            $baseCurrencyCode,
            $destinationCurrencyCode,
            $buyRate,
            $saleRate
        );
    }
}
