<?php

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Exceptions\ExchangeRateGrabberException;

class AlfaBankDOMDocumentWebGrabber extends DOMDocumentWebGrabber
{
    public function getExchangeRates(): array
    {
        $DOMXPathQuery = '//div[contains(@class, \'exchange-data\') and contains(@class, \'department\')]/div[@class=\'exchange-data-item\']';

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
                \CURLOPT_URL => 'https://alfabank.ua/currency-exchange?' . \http_build_query(
                    [
                        'refId' => 'MainpageExchangerate',
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

    protected function getBaseCurrencyCode(\DOMNode $DOMNode): string
    {
        return 'UAH';
    }

    protected function getDestinationCurrencyCode(\DOMNode $DOMNode): string
    {
        $DOMXPathQuery = 'div[@class=\'exchange-data-currency\']';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length !== 1) {
            throw new ExchangeRateGrabberException(
                \sprintf(
                    'DOM was changed. %s (responsible for destination currency code) was not found.',
                    $DOMXPathQuery
                )
            );
        }

        $result = (string) \trim($DOMNodeList->item(0)->nodeValue);

        if (!\preg_match('/^[A-Z]{3}$/', $result)) {
            throw new ExchangeRateGrabberException(
                'DOM was changed. Destination currency code is invalid.'
            );
        }

        return $result;
    }

    protected function getBuyRate(\DOMNode $DOMNode): float
    {
        $DOMXPathQuery = 'div[@class=\'exchange-data-currency-block\']/div[@class=\'exchange-data-currency-item\']/div[@class=\'currency-item-number\']/span[@class=\'rate-number\' and @data-currency=\'' . $this->getDestinationCurrencyCode($DOMNode) . '_BUY\']';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length !== 1) {
            throw new ExchangeRateGrabberException(
                \sprintf('DOM was changed.  %s (responsible for buy rate) was not found.', $DOMXPathQuery)
            );
        }

        $result = \trim($DOMNodeList->item(0)->nodeValue);

        if (!\is_numeric($result)) {
            throw new ExchangeRateGrabberException('DOM was changed. Buy rate is invalid.');
        }

        return (float) $result;
    }

    protected function getSaleRate(\DOMNode $DOMNode): float
    {
        $DOMXPathQuery = 'div[@class=\'exchange-data-currency-block\']/div[@class=\'exchange-data-currency-item\']/div[@class=\'currency-item-number\']/span[@class=\'rate-number\' and @data-currency=\'' . $this->getDestinationCurrencyCode($DOMNode) . '_SALE\']';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length !== 1) {
            throw new ExchangeRateGrabberException(
                \sprintf('DOM was changed. %s (responsible for sale rate) element was not found.', $DOMXPathQuery)
            );
        }

        $result = \trim($DOMNodeList->item(0)->nodeValue);

        if (!\is_numeric($result)) {
            throw new ExchangeRateGrabberException('DOM was changed. Sale rate is invalid.');
        }

        return (float) $result;
    }
}
