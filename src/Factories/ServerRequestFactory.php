<?php

namespace pdeans\Http\Factories;

use Laminas\Diactoros\ServerRequestFactory as PSR17ServerRequestFactory;

/**
 * Class for marshaling a request object from the current PHP environment.
 */
class ServerRequestFactory extends PSR17ServerRequestFactory
{
}
