<?php

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Exceptions\ExchangeRateGrabberException;

class NbuDOMDocumentWebGrabber extends DOMDocumentWebGrabber
{
    public function getExchangeRates(): array
    {
        $DOMXPathQuery = '//table[@id=\'exchangeRates\']/tbody/tr';

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
                \CURLOPT_URL => 'https://bank.gov.ua/ua/markets/exchangerates?' . \http_build_query(
                    [
                        'date' => (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Kiev'))
                            ->format('d.m.Y'),
                        'period' => 'daily',
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
        $DOMXPathQuery = 'td[2]';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new ExchangeRateGrabberException(
                \sprintf(
                    'DOM was changed. %s (responsible for destination currency code) was not found.',
                    $DOMXPathQuery
                )
            );
        }

        $result = \trim($DOMNodeList->item(0)->nodeValue);

        if (!\preg_match('/^[A-Z]{3}$/', $result)) {
            throw new ExchangeRateGrabberException('DOM was changed. Destination currency code is invalid.');
        }

        return $result;
    }

    protected function getBuyRate(\DOMNode $DOMNode): float
    {
        $DOMXPathQuery = 'td[5]';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new ExchangeRateGrabberException(
                \sprintf('DOM was changed. %s (responsible for buy (sale) rate) was not found.', $DOMXPathQuery)
            );
        }

        $result = \str_replace(',', '.', \trim($DOMNodeList->item(0)->nodeValue));

        if (!\is_numeric($result)) {
            throw new ExchangeRateGrabberException('DOM was changed. Buy (sale) rate is invalid.');
        }

        return (float) ($result / $this->getUnit($DOMNode));
    }

    protected function getSaleRate(\DOMNode $DOMNode): float
    {
        return $this->getBuyRate($DOMNode);
    }

    private function getUnit(\DOMNode $DOMNode): int
    {
        $DOMXPathQuery = 'td[3]';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new ExchangeRateGrabberException(
                \sprintf('DOM was changed. %s (responsible for unit) was not found.', $DOMXPathQuery)
            );
        }

        $result = \trim($DOMNodeList->item(0)->nodeValue);

        if (!\is_numeric($result)) {
            throw new ExchangeRateGrabberException('DOM was changed. Unit is invalid.');
        }

        return (int) $result;
    }
}
