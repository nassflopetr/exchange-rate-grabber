<?php

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Exceptions\ExchangeRateGrabberException;

class UkrSibBankDOMDocumentWebGrabber extends DOMDocumentWebGrabber
{
    public function getExchangeRates(): array
    {
        $DOMXPathQuery = '//table[@class=\'currency__table\']/tbody/tr';

        $DOMNodeList = $this->getDOMXPathResponse()->query($DOMXPathQuery);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new ExchangeRateGrabberException(
                \sprintf('DOM was changed. %s was not found.', $DOMXPathQuery)
            );
        }

        return $this->getDOMNodeListExchangeRates($DOMNodeList);
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
                \CURLOPT_URL => 'https://my.ukrsibbank.com/ua/personal/operations/currency_exchange/',
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

    protected function getBaseCurrencyCode(\DOMNode $DOMNode): string
    {
        return 'UAH';
    }

    protected function getDestinationCurrencyCode(\DOMNode $DOMNode): string
    {
        $DOMXPathQuery = 'td[1]/text()';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new ExchangeRateGrabberException(
                \sprintf(
                    'DOM was changed. %s (responsible for destination currency code) was not found.',
                    $DOMXPathQuery
                )
            );
        }

        $result = (string) \preg_replace('/[^A-Z]+/', '', \trim($DOMNodeList->item(0)->textContent));

        if (!\preg_match('/^[A-Z]{3}$/', $result)) {
            throw new ExchangeRateGrabberException('DOM was changed. Destination currency code is invalid.');
        }

        return $result;
    }

    protected function getBuyRate(\DOMNode $DOMNode): float
    {
        $DOMXPathQuery = 'td[2]/text()';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new ExchangeRateGrabberException(
                \sprintf('DOM was changed. %s (responsible for buy rate) was not found.', $DOMXPathQuery)
            );
        }

        $result = \trim($DOMNodeList->item(0)->textContent);

        if (!\is_numeric($result)) {
            throw new ExchangeRateGrabberException('DOM was changed. Buy rate is invalid.');
        }

        return (float) $result;
    }

    protected function getSaleRate(\DOMNode $DOMNode): float
    {
        $DOMXPathQuery = 'td[3]/text()';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new ExchangeRateGrabberException(
                \sprintf('DOM was changed. %s (responsible for sale rate) was not found.', $DOMXPathQuery)
            );
        }

        $result = \trim($DOMNodeList->item(0)->textContent);

        if (!\is_numeric($result)) {
            throw new ExchangeRateGrabberException('DOM was changed. Sale rate is invalid.');
        }

        return (float) $result;
    }
}
