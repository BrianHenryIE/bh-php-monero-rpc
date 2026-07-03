<?php

declare(strict_types=1);

namespace BrianHenryIE\MoneroRpc\Exception;

use Exception;

/**
 * Thrown when monerod returns a JSON-RPC error object.
 *
 * The RPC error's numeric `code` is carried through as the exception code (e.g. -6
 * "Wrong block blob", -48 "multisig is disabled"), and `data` is preserved when present, so
 * consumers can branch on the specific failure rather than string-matching the message.
 *
 * Extends {@see Exception} so existing `catch (\Exception)` sites continue to work.
 */
class MoneroRpcErrorException extends Exception
{
    /**
     * @param mixed $rpcData The error object's `data` field, if any.
     */
    public function __construct(
        string $message,
        int $code,
        private readonly mixed $rpcData = null,
    ) {
        parent::__construct($message, $code);
    }

    /**
     * The `data` field of the JSON-RPC error object, or null if it carried none.
     */
    public function getRpcData(): mixed
    {
        return $this->rpcData;
    }
}
