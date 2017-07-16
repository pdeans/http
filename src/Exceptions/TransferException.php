<?php

namespace pdeans\Http\Exceptions;

use pdeans\Http\Contracts\ExceptionInterface;
use RuntimeException;

/**
 * Transfer Exception
 *
 * Base class for http transfer exceptions
 */
class TransferException extends RuntimeException implements ExceptionInterface {}