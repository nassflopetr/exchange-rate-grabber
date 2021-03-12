<?php

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Model\ExchangeRate;
use NassFloPetr\ExchangeRateGrabber\Exceptions\ExchangeRateGrabberException;

abstract class JSONWebGrabber extends WebGrabber
{
    abstract protected function getBaseCurrencyCode(array $decodedJSONResponseItem): string;

    abstract protected function getDestinationCurrencyCode(array $decodedJSONResponseItem): string;

    abstract protected function getBuyRate(array $decodedJSONResponseItem): float;

    abstract protected function getSaleRate(array $decodedJSONResponseItem): float;

    protected function getDecodedJSONResponse(): array
    {
        $response = $this->getResponse();

        try {
            return \json_decode($response, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ExchangeRateGrabberException('JSON decoding failed. ' . $e->getMessage());
        }
    }

    protected function getDecodedJSONResponseItemExchangeRate(array $decodedJSONResponseItem): ?ExchangeRate
    {
        return $this->getExchangeRate(
            $this->getBaseCurrencyCode($decodedJSONResponseItem),
            $this->getDestinationCurrencyCode($decodedJSONResponseItem),
            $this->getBuyRate($decodedJSONResponseItem),
            $this->getSaleRate($decodedJSONResponseItem)
        );
    }

    protected function getDecodedJSONResponseExchangeRates(array $decodedJSONResponse): array
    {
        $exchangeRates = [];

        foreach ($decodedJSONResponse as $decodedJSONResponseItem) {
            $exchangeRate = $this->getDecodedJSONResponseItemExchangeRate($decodedJSONResponseItem);

            if (!\is_null($exchangeRate)) {
                $exchangeRates[] = $exchangeRate;
            }
        }

        return $exchangeRates;
    }
}
