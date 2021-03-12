<?php

namespace NassFloPetr\ExchangeRateGrabber\Model;

use NassFloPetr\ExchangeRateGrabber\Grabbers\Grabber;
use NassFloPetr\ExchangeRateGrabber\Observers\ExchangeRateObserver;
use NassFloPetr\ExchangeRateGrabber\Exceptions\ExchangeRateException;

class ExchangeRate
{
    private Grabber $grabber;
    private string $baseCurrencyCode;
    private string $destinationCurrencyCode;
    private float $buyRate;
    private float $saleRate;

    private \DateTime $timestamp;
    private \SplObjectStorage $observers;

    public function __construct(
        Grabber $grabber,
        string $baseCurrencyCode,
        string $destinationCurrencyCode,
        float $buyRate,
        float $saleRate,
        \DateTime $timestamp = null
    )
    {
        $this->grabber = $grabber;
        $this->baseCurrencyCode = $baseCurrencyCode;
        $this->destinationCurrencyCode = $destinationCurrencyCode;

        $this->setExchangeRate(
            $buyRate,
            $saleRate,
            $timestamp
        );

        $this->observers = new \SplObjectStorage();
    }

    public function __serialize(): array
    {
        return [
            'grabber_class' => \get_class($this->grabber),
            'grabber' => \serialize($this->grabber),
            'base_currency_code' => $this->baseCurrencyCode,
            'destination_currency_code' => $this->destinationCurrencyCode,
            'buy_rate' => $this->buyRate,
            'sale_rate' => $this->saleRate,
            'timestamp' => \serialize($this->timestamp),
            'observers' => $this->observers->serialize(),
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->__construct(
            \unserialize($data['grabber'], ['allowed_classes' => [$data['grabber_class']]]),
            $data['base_currency_code'],
            $data['destination_currency_code'],
            $data['buy_rate'],
            $data['sale_rate'],
            \unserialize($data['timestamp'], ['allowed_classes' => [\DateTime::class]])
        );

        $this->observers->unserialize($data['observers']);
    }

    public function attach(ExchangeRateObserver $observer): void
    {
        $this->observers->attach($observer);
    }

    public function detach(ExchangeRateObserver $observer): void
    {
        $this->observers->detach($observer);
    }

    public function notifyExchangeRateCreated(): void
    {
        foreach ($this->observers as $observer) {
            $observer->exchangeRateCreated($this);
        }
    }

    public function notifyExchangeRateUpdated(ExchangeRate $preExchangeRate): void
    {
        foreach ($this->observers as $observer) {
            $observer->exchangeRateUpdated($preExchangeRate, $this);
        }
    }

    public function notifyExchangeRateChanged(ExchangeRate $preExchangeRate): void
    {
        foreach ($this->observers as $observer) {
            $observer->exchangeRateChanged($preExchangeRate, $this);
        }
    }

    public function updateExchangeRate(float $buyRate, float $saleRate, \DateTime $timestamp = null): void
    {
        // TODO: check clone
        $preExchangeRate = clone $this;

        $this->setExchangeRate($buyRate, $saleRate, $timestamp);

        $this->notifyExchangeRateUpdated($preExchangeRate);

        if ($this->isExchangeRateChanged($preExchangeRate)) {
            $this->notifyExchangeRateChanged($preExchangeRate);
        }
    }

    public function refresh(): void
    {
        $exchangeRates = $this->grabber->getExchangeRates();

        $exchangeRatesCount = \count($exchangeRates);

        if ($exchangeRatesCount !== 1) {
            throw new ExchangeRateException(
                \sprintf('Expected 1 exchange rate, but %d was found.', $exchangeRatesCount)
            );
        }

        $latestExchangeRate = $exchangeRates[0];

        $this->updateExchangeRate(
            $latestExchangeRate->getBuyRate(),
            $latestExchangeRate->getSaleRate(),
            $latestExchangeRate->getTimestamp()
        );
    }

    public function getBaseCurrencyCode(): string
    {
        return $this->baseCurrencyCode;
    }

    public function getDestinationCurrencyCode(): string
    {
        return $this->destinationCurrencyCode;
    }

    public function getBuyRate(): float
    {
        return $this->buyRate;
    }

    public function getSaleRate(): float
    {
        return $this->saleRate;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function getGrabber(): Grabber
    {
        return $this->grabber;
    }

    private function setExchangeRate(float $buyRate, float $saleRate, \DateTime $timestamp = null): void
    {
        $this->buyRate = $buyRate;
        $this->saleRate = $saleRate;

        $this->timestamp = \is_null($timestamp) ? new \DateTime() : $timestamp;
    }

    private function isExchangeRateChanged(ExchangeRate $preExchangeRate): bool
    {
        return
            !(\abs($this->getBuyRate() - $preExchangeRate->getBuyRate()) < \PHP_FLOAT_EPSILON)
            || !(\abs($this->getSaleRate() - $preExchangeRate->getSaleRate()) < \PHP_FLOAT_EPSILON);
    }
}