<?php

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

abstract class WebGrabber extends Grabber
{
    abstract protected function getResponse(): string;
}
