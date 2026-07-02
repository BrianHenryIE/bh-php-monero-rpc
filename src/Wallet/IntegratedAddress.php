<?php

/**
 * $ curl http://127.0.0.1:18082/json_rpc -d '{"jsonrpc":"2.0","id":"0","method":"make_integrated_address","params":{"standard_address":"55LTR8KniP4LQGJSPtbYDacR7dz8RBFnsfAKMaMuwUNYX6aQbBcovzDPyrQF9KXF9tVU6Xk3K8no1BywnJX6GvZX8yJsXvt"}}' -H 'Content-Type: application/json'
 * {
 * "id": "0",
 * "jsonrpc": "2.0",
 * "result": {
 * "integrated_address": "5F38Rw9HKeaLQGJSPtbYDacR7dz8RBFnsfAKMaMuwUNYX6aQbBcovzDPyrQF9KXF9tVU6Xk3K8no1BywnJX6GvZXCkbHUXdPHyiUeRyokn",
 * "payment_id": "420fa29b2d9a49f5"
 * }
 * }
 */

namespace BrianHenryIE\MoneroRpc\Wallet;

interface IntegratedAddress
{
    public function getIntegratedAddress(): string;
    public function getPaymentId(): string;
}
