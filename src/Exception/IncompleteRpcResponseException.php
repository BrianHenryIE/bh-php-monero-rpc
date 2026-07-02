<?php

/**
 * Thrown when an RPC response is missing a field that its typed response model requires.
 *
 * Response models are immutable `readonly` classes whose fields are required by default (see
 * PHP84_READONLY_MODELS_PLAN.md design decision 5): a silently-defaulted value could flow into a
 * payment decision downstream, so a missing required field is a loud error rather than a fabricated
 * default.
 *
 * The exception MESSAGE carries the RPC method, the target model class, the missing property
 * name(s) and the keys that WERE present — enough to debug from a log. The raw response body is
 * deliberately kept OUT of the message (wallet responses can contain secrets, e.g. `query_key`
 * returns the mnemonic and `get_tx_key` returns transaction keys, and exception messages are
 * routinely logged); it is available via {@see getResponseBody()} for callers that need it.
 */

namespace BrianHenryIE\MoneroRpc\Exception;

use Exception;
use Throwable;

class IncompleteRpcResponseException extends Exception
{
    public function __construct(
        string $message,
        private readonly string $responseBody,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * The raw JSON response body that failed to hydrate.
     *
     * Not included in {@see getMessage()} because it may contain secrets; retrieve it explicitly
     * when needed (e.g. when handling the exception outside of a logging context).
     */
    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}
