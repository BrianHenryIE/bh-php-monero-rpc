<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

/**
 * Response of the daemon `set_log_categories` RPC, echoing the categories now in effect.
 */
final readonly class LogCategories extends ResponseBase
{
    /**
     * @param string $categories The active log categories, e.g. "*:WARNING".
     */
    public function __construct(
        public string $categories,
        string $status,
        bool $untrusted,
    ) {
        parent::__construct($status, $untrusted);
    }
}
