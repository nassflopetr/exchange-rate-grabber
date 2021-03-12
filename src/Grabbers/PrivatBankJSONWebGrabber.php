<?php

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Exceptions\ExchangeRateGrabberException;

class PrivatBankJSONWebGrabber extends JSONWebGrabber
{
    public function getExchangeRates(): array
    {
        return $this->getDecodedJSONResponseExchangeRates($this->getDecodedJSONResponse());
    }

    protected function getResponse(): string
    {
        $ch = \curl_init();

        if (!$ch) {
            throw new ExchangeRateGrabberException(
                \sprintf('Can\'t create %s instance.', \CurlHandle::class)
            );
        }

        if (!\curl_setopt_array($ch, [
                \CURLOPT_URL => 'https://api.privatbank.ua/p24api/pubinfo?' . \http_build_query(
                    [
                        'json' => '',
                        'exchange' => '',
                        'coursid' => 5,
                    ]
                ),
                \CURLOPT_HEADER => false,
                \CURLOPT_RETURNTRANSFER => true,
                \CURLOPT_CONNECTTIMEOUT => 30,
                \CURLOPT_TIMEOUT => 30,
            ])
        ) {
            throw new ExchangeRateGrabberException(\curl_error($ch));
        }

        $response = \curl_exec($ch);

        if (!$response || \curl_errno($ch) !== 0) {
            throw new ExchangeRateGrabberException(\curl_error($ch));
        } elseif (\curl_getinfo($ch, \CURLINFO_RESPONSE_CODE) !== 200) {
            throw new ExchangeRateGrabberException(
                \sprintf(
                    'Open %s stream failed. Response code %d.',
                    \curl_getinfo($ch, \CURLINFO_EFFECTIVE_URL),
                    \curl_getinfo($ch, \CURLINFO_HTTP_CODE)
                )
            );
        }

        \curl_close($ch);

        return $response;
    }

    protected function getBaseCurrencyCode(array $decodedJSONResponseItem): string
    {
        if (!\array_key_exists('base_ccy', $decodedJSONResponseItem)) {
            throw new ExchangeRateGrabberException(
                'Response JSON structure was changed. No key \'base_ccy\' found in array (json) structure.'
            );
        }

        $result = \trim($decodedJSONResponseItem['base_ccy']);

        if (!\preg_match('/^[A-Z]{3}$/', $result)) {
            throw new ExchangeRateGrabberException(
                'Response JSON structure was changed. Base currency code is invalid.'
            );
        }

        return $result;
    }

    protected function getDestinationCurrencyCode(array $decodedJSONResponseItem): string
    {
        if (!\array_key_exists('ccy', $decodedJSONResponseItem)) {
            throw new ExchangeRateGrabberException(
                'Response JSON structure was changed. No key \'ccy\' found in array (json) structure.'
            );
        }

        $result = \trim($decodedJSONResponseItem['ccy']);

        if (!\preg_match('/^[A-Z]{3}$/', $result)) {
            throw new ExchangeRateGrabberException(
                'Response JSON structure was changed. Destination currency code is invalid.'
            );
        }

        return $result;
    }

    protected function getBuyRate(array $decodedJSONResponseItem): float
    {
        if (!\array_key_exists('buy', $decodedJSONResponseItem)) {
            throw new ExchangeRateGrabberException(
                'Response JSON structure was changed. No key \'buy\' found in array (json) structure.'
            );
        }

        $result = \trim($decodedJSONResponseItem['buy']);

        if (!\is_numeric($result)) {
            throw new ExchangeRateGrabberException('Response JSON structure was changed. Buy rate is invalid.');
        }

        return (float) $result;
    }

    protected function getSaleRate(array $decodedJSONResponseItem): float
    {
        if (!\array_key_exists('sale', $decodedJSONResponseItem)) {
            throw new ExchangeRateGrabberException(
                'Response JSON structure was changed. No key \'sale\' found in array (json) structure.'
            );
        }

        $result = \trim($decodedJSONResponseItem['sale']);

        if (!\is_numeric($result)) {
            throw new ExchangeRateGrabberException('Response JSON structure was changed. Sale rate is invalid.');
        }

        return (float) $result;
    }
}
