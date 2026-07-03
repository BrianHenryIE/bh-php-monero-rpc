<?php

namespace BrianHenryIE\MoneroRpc\Wallet;

use BrianHenryIE\MoneroRpc\MoneroAmount;

/**
 * The parsed components of a monero: URI (the `uri` sub-object of `parse_uri`).
 */
final readonly class ParsedUri
{
    /**
     * @param string       $address       The recipient address.
     * @param MoneroAmount $amount         Requested amount, in atomic units (0 if unspecified).
     * @param string       $paymentId     Payment id, if any.
     * @param string       $recipientName Recipient name, if any.
     * @param string       $txDescription Transaction description, if any.
     */
    public function __construct(
        public string $address,
        public MoneroAmount $amount,
        public string $paymentId,
        public string $recipientName,
        public string $txDescription,
    ) {
    }
}
