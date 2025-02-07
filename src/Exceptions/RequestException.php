<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

namespace pdeans\Http\Exceptions;

use Exception;
use Psr\Http\Message\RequestInterface;

/**
 * Request Exception
 *
 * Failed http request exception class.
 */
class RequestException extends TransferException
{
    /**
     * Request object
     *
     * @var \Psr\Http\Message\RequestInterface
     */
    private $request;

    /**
     * Create request exception object.
     */
    public function __construct(string $message, RequestInterface $request, Exception|null $last_exception = null)
    {
        $this->request = $request;

        // \TransferException => \RuntimeException
        parent::__construct($message, 0, $last_exception);
    }

    /**
     * Get the request object.
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
