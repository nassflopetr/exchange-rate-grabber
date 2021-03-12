<?php

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Model\ExchangeRate;
use NassFloPetr\ExchangeRateGrabber\Exceptions\ExchangeRateGrabberException;

abstract class DOMDocumentWebGrabber extends WebGrabber
{
    abstract protected function getBaseCurrencyCode(\DOMNode $DOMNode): string;

    abstract protected function getDestinationCurrencyCode(\DOMNode $DOMNode): string;

    abstract protected function getBuyRate(\DOMNode $DOMNode): float;

    abstract protected function getSaleRate(\DOMNode $DOMNode): float;

    protected function getDOMDocumentResponse(): \DOMDocument
    {
        $response = $this->getResponse();
        $DOMDocument = new \DOMDocument();

        try {
            $DOMDocument->loadHTML($response);

            if (!$DOMDocument) {
                $error = \libxml_get_last_error();

                throw new ExchangeRateGrabberException(
                    ($error instanceof \libXMLError) ? \serialize($error) : 'Can\'t create DOMDocument object.'
                );
            }
        } catch (\Exception $e) {
            if ($e->getSeverity() !== \E_WARNING) {
                throw new ExchangeRateGrabberException($e->getMessage());
            }
        }

        return $DOMDocument;
    }

    protected function getDOMXPathResponse(): \DOMXPath
    {
        return new \DOMXPath($this->getDOMDocumentResponse());
    }

    protected function getDOMNodeDOMXPath(\DOMNode $DOMNode): \DOMXPath
    {
        return new \DOMXPath($DOMNode->ownerDocument);
    }

    protected function getDOMNodeExchangeRate(\DOMNode $DOMNode): ?ExchangeRate
    {
        return $this->getExchangeRate(
            $this->getBaseCurrencyCode($DOMNode),
            $this->getDestinationCurrencyCode($DOMNode),
            $this->getBuyRate($DOMNode),
            $this->getSaleRate($DOMNode)
        );
    }

    protected function getDOMNodeListExchangeRates(\DOMNodeList $DOMNodeList): array
    {
        $exchangeRates = [];

        foreach ($DOMNodeList as $DOMNode) {
            $exchangeRate = $this->getDOMNodeExchangeRate($DOMNode);

            if (!\is_null($exchangeRate)) {
                $exchangeRates[] = $exchangeRate;
            }
        }

        return $exchangeRates;
    }
}
