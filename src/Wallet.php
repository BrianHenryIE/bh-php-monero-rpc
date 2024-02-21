<?php

// https://www.getmonero.org/resources/user-guides/monero-wallet-cli.html

/**
 *
 * monerophp/walletRPC
 *
 * A class for making calls to monero-wallet-rpc using PHP
 * https://github.com/monero-integrations/monerophp
 *
 * Using work from
 *   CryptoChangements [Monero_RPC] <bW9uZXJv@gmail.com> (https://github.com/cryptochangements34)
 *   Serhack [Monero Integrations] <nico@serhack.me> (https://serhack.me)
 *   TheKoziTwo [xmr-integration] <thekozitwo@gmail.com>
 *   Kacper Rowinski [jsonRPCClient] <krowinski@implix.com>
 *
 * @author     Monero Integrations Team <support@monerointegrations.com> (https://github.com/monero-integrations)
 * @copyright  2018
 * @license    MIT
 *
 * ============================================================================
 *
 * // See example.php for more examples
 *
 * // Initialize class
 * $walletRPC = new walletRPC();
 *
 * // Examples:
 * $address = $walletRPC->get_address();
 * $signed = $walletRPC->sign('The Times 03/Jan/2009 Chancellor on brink of second bailout for banks');
 *
 */

namespace BrianHenryIE\MoneroRpc;

use Exception;

class Wallet extends RpcClient
{
  /**
   * Convert from moneroj to tacoshi (piconero)
   *
   * @used-by Wallet::sweepSingle()
   *
   * @param  int  $amount  Amount (in monero) to transform to tacoshi (piconero)  (optional)
   */
    public function _transform(int $amount = 0): int
    {
        return intval(bcmul($amount, 1000000000000));
    }

  /**
   * Look up an account's balance
   *
   * @param  int  $accountIndex  Index of account to look up  (optional)
   *
   * @return object  Example: {
   *   "balance": 140000000000,
   *   "unlocked_balance": 50000000000
   * }
   *
   */
    public function getBalance(int $accountIndex = 0)
    {
        $params = array('account_index' => $accountIndex);
        return $this->runJsonRpc('get_balance', $params);
    }

  /**
   * Look up wallet address(es)
   *
   * @param  int  $accountIndex  Index of account to look up     (optional)
   * @param  int  $addressIndex  Index of subaddress to look up  (optional)
   *
   * @return object  Example: {
   *   "address": "A2XE6ArhRkVZqepY2DQ5QpW8p8P2dhDQLhPJ9scSkW6q9aYUHhrhXVvE8sjg7vHRx2HnRv53zLQH4ATSiHHrDzcSFqHpARF",
   *   "addresses": [
   *     {
   *       "address": "A2XE6ArhRkVZqepY2DQ5QpW8p8P2dhDQLhPJ9scSkW6q9aYUHhrhXVvE8sjg7vHRx2HnRv53zLQH4ATSiHHrDzcSFqHpARF",
   *       "address_index": 0,
   *       "label": "Primary account",
   *       "used": true
   *     }, {
   *       "address": "Bh3ttLbjGFnVGCeGJF1HgVh4DfCaBNpDt7PQAgsC2GFug7WKskgfbTmB6e7UupyiijiHDQPmDC7wSCo9eLoGgbAFJQaAaDS",
   *       "address_index": 1,
   *       "label": "",
   *       "used": true
   *     }
   *   ]
   * }
   *
   */
    public function getAddress(int $accountIndex = 0, int $addressIndex = 0)
    {
        $params = array( 'account_index' => $accountIndex, 'address_index' => $addressIndex);
        return $this->runJsonRpc('get_address', $params);
    }

    /**
     * @param string $address Monero address
     * @return object Example: {
    * "index": {
    * "major": 0,
    * "minor": 1
    * }
    * }
     */
    public function getAddressIndex(string $address)
    {
        $params = array('address' => $address);
        return $this->runJsonRpc('get_address_index', $params);
    }

  /**
   * Create a new subaddress
   *
   * @param  int  $accountIndex  The subaddress account index
   * @param  string  $label          A label to apply to the new subaddress
   *
   * @return object  Example: {
   *   "address": "Bh3ttLbjGFnVGCeGJF1HgVh4DfCaBNpDt7PQAgsC2GFug7WKskgfbTmB6e7UupyiijiHDQPmDC7wSCo9eLoGgbAFJQaAaDS"
   *   "address_index": 1
   * }
   *
   */
    public function createAddress(int $accountIndex = 0, string $label = '')
    {
        $params = array( 'account_index' => $accountIndex, 'label' => $label);
        $createAddressMethod = $this->runJsonRpc('create_address', $params);

        $save = $this->store(); // Save wallet state after subaddress creation

        return $createAddressMethod;
    }

  /**
   * Label a subaddress
   *
   * @param  int  The index of the subaddress to label
   * @param  string  The label to apply
   */
    public function labelAddress(int $index, string $label)
    {
        $params = array('index' => $index ,'label' => $label);
        return $this->runJsonRpc('label_address', $params);
    }

  /**
   * Look up wallet accounts
   *
   * @param  string $tag Optional filtering by tag
   *
   * @return object  Example: {
   *   "subaddress_accounts": {
   *     "0": {
   *       "account_index": 0,
   *       "balance": 2808597352948771,
   *       "base_address": "A2XE6ArhRkVZqepY2DQ5QpW8p8P2dhDQLhPJ9scSkW6q9aYUHhrhXVvE8sjg7vHRx2HnRv53zLQH4ATSiHHrDzcSFqHpARF",
   *       "label": "Primary account",
   *       "tag": "",
   *       "unlocked_balance": 2717153096298162
   *     },
   *     "1": {
   *       "account_index": 1,
   *       "balance": 0,
   *       "base_address": "BcXKsfrvffKYVoNGN4HUFfaruAMRdk5DrLZDmJBnYgXrTFrXyudn81xMj7rsmU5P9dX56kRZGqSaigUxUYoaFETo9gfDKx5",
   *       "label": "Secondary account",
   *       "tag": "",
   *       "unlocked_balance": 0
   *    },
   *    "total_balance": 2808597352948771,
   *    "total_unlocked_balance": 2717153096298162
   * }
   *
   */
    public function getAccounts(?string $tag = null)
    {
        return $tag
            ? $this->runJsonRpc('get_accounts', array('tag' => $tag))
            : $this->runJsonRpc('get_accounts');
    }

  /**
   * Create a new account
   *
   * @param  string  $label  Label to apply to new account
   */
    public function createAccount(string $label = '')
    {
        $params = array('label' => $label);
        $createAccountMethod = $this->runJsonRpc('create_account', $params);

        $save = $this->store(); // Save wallet state after account creation

        return $createAccountMethod;
    }

  /**
   * Label an account
   *
   * @param  int $accountIndex  Index of account to label
   * @param  string $label          Label to apply
   */
    public function labelAccount(int $accountIndex, string $label)
    {
        $params = array( 'account_index' => $accountIndex, 'label' => $label);
        $labelAccountMethod = $this->runJsonRpc('label_account', $params);

        $save = $this->store(); // Save wallet state after account label

        return $labelAccountMethod;
    }

  /**
   * Look up account tags
   *
   * @return object  Example: {
   *   "account_tags": {
   *     "0": {
   *       "accounts": {
   *         "0": 0,
   *         "1": 1
   *       },
   *       "label": "",
   *       "tag": "Example tag"
   *     }
   *   }
   * }
   *
   */
    public function getAccountTags()
    {
        return $this->runJsonRpc('get_account_tags');
    }

  /**
   * Tag accounts
   *
   * @param  array   $accounts  The indices of the accounts to tag
   * @param  string  $tag       Tag to apply
   */
    public function tagAccounts($accounts, string $tag)
    {
        $params = array('accounts' => $accounts, 'tag' => $tag);
        $tagAccountsMethod = $this->runJsonRpc('tag_accounts', $params);

        $save = $this->store(); // Save wallet state after account tagging

        return $tagAccountsMethod;
    }

  /**
   * Untag accounts
   *
   * @param  array   $accounts  The indices of the accounts to untag
   */
    public function untagAccounts($accounts)
    {
        $params = array('accounts' => $accounts);
        $untagAccountsMethod = $this->runJsonRpc('untag_accounts', $params);

        $save = $this->store(); // Save wallet state after untagging accounts

        return $untagAccountsMethod;
    }

  /**
   * Describe a tag
   *
   * @param  string  $tag          Tag to describe
   * @param  string  $description  Description to apply to tag
   *
   * @return object  Example: {
   *   // TODO example
   * }
   *
   */
    public function setAccountTagDescription(string $tag, string $description)
    {
        $params = array('tag' => $tag, 'description' => $description);
        $setAccountTagDescriptionMethod = $this->runJsonRpc('set_account_tag_description', $params);

        $save = $this->store(); // Save wallet state after describing tag

        return $setAccountTagDescriptionMethod;
    }

  /**
   * Look up how many blocks are in the longest chain known to the wallet
   *
   * @return object  Example: {
   *   "height": 994310
   * }
   *
   */
    public function getHeight()
    {
        return $this->runJsonRpc('get_height');
    }

  /**
   * Send monero
   * Parameters can be passed in individually (as listed below) or as an object/dictionary (as listed at bottom)
   * To send to multiple recipients, use the object/dictionary (bottom) format and pass an array of recipient addresses and amount arrays in the destinations field (as in "destinations = [['amount' => 1, 'address' => ...], ['amount' => 2, 'address' => ...]]")
   *
   * @param  string   $amount           Amount of monero to send
   * @param  string   $address          Address to receive funds
   * @param  string   $paymentId       Payment ID                                                (optional)
   * @param  int   $mixin            Mixin number (ringsize - 1)                               (optional)
   * @param  int   $accountIndex    Account to send from                                      (optional)
   * @param  string   $subaddrIndices  Comma-separated list of subaddress indices to spend from  (optional)
   * @param  int   $priority         Transaction priority                                      (optional)
   * @param  int   $unlockTime      UNIX time or block height to unlock output                (optional)
   * @param  boolean  $doNotRelay     Do not relay transaction                                  (optional)
   *
   *   OR
   *
   * @param  object  $params            Array containing any of the options listed above, where only amount and address or a destination's array are required
   *
   * @return object  Example: {
   *   "amount": "1000000000000",
   *   "fee": "1000020000",
   *   "tx_hash": "c60a64ddae46154a75af65544f73a7064911289a7760be8fb5390cb57c06f2db",
   *   "tx_key": "805abdb3882d9440b6c80490c2d6b95a79dbc6d1b05e514131a91768e8040b04"
   * }
   *
   */
    public function transfer(
        string $amount,
        string $address = '',
        string $paymentId = '',
        int $mixin = 15,
        int $accountIndex = 0,
        string $subaddrIndices = '',
        int $priority = 2,
        int $unlockTime = 0,
        bool $doNotRelay = false,
        int $ringsize = 11
    ) {
        if (is_array($amount)) { // Parameters passed in as object/dictionary
            $params = $amount;

            if (array_key_exists('destinations', $params)) {
                $destinations = $params['destinations'];

                if (!is_array($destinations)) {
                    throw new Exception('Error: destinations must be an array');
                }

                foreach ($destinations as $destinationIndex => $destination) {
                    if (array_key_exists('amount', $destination)) {
                        $destinations[$destinationIndex]['amount'] = $this->_transform($destination['amount']);
                    } else {
                        throw new Exception('Error: Amount required');
                    }
                    if (!array_key_exists('address', $destination)) {
                        throw new Exception('Error: Address required');
                    }
                }
            } else {
                if (array_key_exists('amount', $params)) {
                    $amount = $params['amount'];
                } else {
                    throw new Exception('Error: Amount required');
                }
                if (array_key_exists('address', $params)) {
                    $address = $params['address'];
                } else {
                    throw new Exception('Error: Address required');
                }
                $destinations = array(array('amount' => $this->_transform($amount), 'address' => $address));
            }
            if (array_key_exists('payment_id', $params)) {
                throw new Exception('Error: Payment ids have been deprecated.');
            }
            if (array_key_exists('mixin', $params)) {
                $mixin = $params['mixin'];
            }
            if (array_key_exists('account_index', $params)) {
                $accountIndex = $params['account_index'];
            }
            if (array_key_exists('subaddr_indices', $params)) {
                $subaddrIndices = $params['subaddr_indices'];
            }
            if (array_key_exists('priority', $params)) {
                $priority = $params['priority'];
            }
            if (array_key_exists('unlock_time', $params)) {
                $unlockTime = $params['unlock_time'];
            }
            if (array_key_exists('do_not_relay', $params)) {
                $doNotRelay = $params['do_not_relay'];
            }
        } else { // Legacy parameters used
            $destinations = array(array('amount' => $this->_transform($amount), 'address' => $address));
        }

        $params = array( 'destinations' => $destinations, 'mixin' => $mixin, 'get_tx_key' => true, 'account_index' => $accountIndex, 'subaddr_indices' => $subaddrIndices, 'priority' => $priority, 'do_not_relay' => $doNotRelay, 'ringsize' => $ringsize);
        $transferMethod = $this->runJsonRpc('transfer', $params);

        $save = $this->store(); // Save wallet state after transfer

        return $transferMethod;
    }

  /**
   * Same as transfer, but splits transfer into more than one transaction if necessary
   *
   */
    public function transferSplit($amount, $address = '', $paymentId = '', $mixin = 15, $accountIndex = 0, $subaddrIndices = '', $priority = 2, $unlockTime = 0, $doNotRelay = false)
    {
        if (is_array($amount)) { // Parameters passed in as object/dictionary
            $params = $amount;

            if (array_key_exists('destinations', $params)) {
                $destinations = $params['destinations'];

                if (!is_array($destinations)) {
                    throw new Exception('Error: destinations must be an array');
                }

                foreach ($destinations as $destination) {
                    if (array_key_exists('amount', $destinations[$destination])) {
                        $destinations[$destination]['amount'] = $this->_transform($destinations[$destination]['amount']);
                    } else {
                        throw new Exception('Error: Amount required');
                    }
                    if (!array_key_exists('address', $destinations[$destination])) {
                        throw new Exception('Error: Address required');
                    }
                }
            } else {
                if (array_key_exists('amount', $params)) {
                    $amount = $params['amount'];
                } else {
                    throw new Exception('Error: Amount required');
                }
                if (array_key_exists('address', $params)) {
                    $address = $params['address'];
                } else {
                    throw new Exception('Error: Address required');
                }
                $destinations = array(array('amount' => $this->_transform($amount), 'address' => $address));
            }
            if (array_key_exists('mixin', $params)) {
                $mixin = $params['mixin'];
            }
            if (array_key_exists('payment_id', $params)) {
                $paymentId = $params['payment_id'];
            }
            if (array_key_exists('account_index', $params)) {
                $accountIndex = $params['account_index'];
            }
            if (array_key_exists('subaddr_indices', $params)) {
                $subaddrIndices = $params['subaddr_indices'];
            }
            if (array_key_exists('priority', $params)) {
                $priority = $params['priority'];
            }
            if (array_key_exists('unlock_time', $params)) {
                $unlockTime = $params['unlock_time'];
            }
            if (array_key_exists('do_not_relay', $params)) {
                $doNotRelay = $params['do_not_relay'];
            }
        } else { // Legacy parameters used
            $destinations = array(array('amount' => $this->_transform($amount), 'address' => $address));
        }

        $params = array( 'destinations' => $destinations, 'mixin' => $mixin, 'get_tx_key' => true, 'account_index' => $accountIndex, 'subaddr_indices' => $subaddrIndices, 'payment_id' => $paymentId, 'priority' => $priority, 'unlock_time' => $unlockTime, 'do_not_relay' => $doNotRelay);
        $transferMethod = $this->runJsonRpc('transfer_split', $params);

        $save = $this->store(); // Save wallet state after transfer

        return $transferMethod;
    }

  /**
   * Send all dust outputs back to the wallet
   *
   * @return object  Example: {
   *   // TODO example
   * }
   *
   */
    public function sweepDust()
    {
        return $this->runJsonRpc('sweep_dust');
    }

  /**
   * Send all unmixable outputs back to the wallet
   *
   * @return object  Example: {
   *   // TODO example
   * }
   *
   */
    public function sweepUnmixable()
    {
        return $this->runJsonRpc('sweep_unmixable');
    }

  /**
   * Send all unlocked outputs from an account to an address
   *
   * @param  string   $address          Address to receive funds
   * @param  string   $subaddrIndices  Comma-separated list of subaddress indices to sweep  (optional)
   * @param  int   $accountIndex    Index of the account to sweep                        (optional)
   * @param  string   $paymentId       Payment ID                                           (optional)
   * @param  int   $mixin            Mixin number (ringsize - 1)                          (optional)
   * @param  int   $priority         Payment ID                                           (optional)
   * @param  number   $belowAmount     Only send outputs below this amount                  (optional)
   * @param  int   $unlockTime      UNIX time or block height to unlock output           (optional)
   * @param  boolean  $doNotRelay     Do not relay transaction                             (optional)
   *
   *   OR
   *
   * @param  object  $params            Array containing any of the options listed above, where only address is required
   *
   * @return object  Example: {
   *   "amount": "1000000000000",
   *   "fee": "1000020000",
   *   "tx_hash": "c60a64ddae46154a75af65544f73a7064911289a7760be8fb5390cb57c06f2db",
   *   "tx_key": "805abdb3882d9440b6c80490c2d6b95a79dbc6d1b05e514131a91768e8040b04"
   * }
   *
   */
    public function sweepAll(
        string $address,
        $subaddrIndices = '',
        int $accountIndex = 0,
        string $paymentId = '',
        int $mixin = 15,
        int $priority = 2,
        $belowAmount = 0,
        int $unlockTime = 0,
        bool $doNotRelay = false
    ) {
        if (is_array($address)) { // Parameters passed in as object/dictionary
            $params = $address;

            if (array_key_exists('address', $params)) {
                $address = $params['address'];
            } else {
                throw new Exception('Error: Address required');
            }
            if (array_key_exists('subaddr_indices', $params)) {
                $subaddrIndices = $params['subaddr_indices'];
            }
            if (array_key_exists('account_index', $params)) {
                $accountIndex = $params['account_index'];
            }
            if (array_key_exists('payment_id', $params)) {
                $paymentId = $params['payment_id'];
            }
            if (array_key_exists('mixin', $params)) {
                $mixin = $params['mixin'];
            }
            if (array_key_exists('priority', $params)) {
                $priority = $params['priority'];
            }
            if (array_key_exists('below_amount', $params)) {
                $belowAmount = $params['below_amount'];
            }
            if (array_key_exists('unlock_time', $params)) {
                $unlockTime = $params['unlock_time'];
            }
            if (array_key_exists('do_not_relay', $params)) {
                $doNotRelay = $params['do_not_relay'];
            }
        }

        $params = array( 'address' => $address, 'mixin' => $mixin, 'get_tx_key' => true, 'subaddr_indices' => $subaddrIndices, 'account_index' => $accountIndex, 'payment_id' => $paymentId, 'priority' => $priority, 'below_amount' => $this->_transform($belowAmount), 'unlock_time' => $unlockTime, 'do_not_relay' => $doNotRelay);
        $sweepAllMethod = $this->runJsonRpc('sweep_all', $params);

        $save = $this->store(); // Save wallet state after transfer

        return $sweepAllMethod;
    }

  /**
   * Sweep a single key image to an address
   *
   * @param  string   $keyImage     Key image to sweep
   * @param  string   $address       Address to receive funds
   * @param  string   $paymentId    Payment ID                                  (optional)
   * @param  number   $belowAmount  Only send outputs below this amount         (optional)
   * @param  int   $mixin         Mixin number (ringsize - 1)                 (optional)
   * @param  int   $priority      Payment ID                                  (optional)
   * @param  int   $unlockTime   UNIX time or block height to unlock output  (optional)
   * @param  boolean  $doNotRelay  Do not relay transaction                    (optional)
   *
   *   OR
   *
   * @param  object  $params         Array containing any of the options listed above, where only address is required
   *
   * @return object  Example: {
   *   "amount": "1000000000000",
   *   "fee": "1000020000",
   *   "tx_hash": "c60a64ddae46154a75af65544f73a7064911289a7760be8fb5390cb57c06f2db",
   *   "tx_key": "805abdb3882d9440b6c80490c2d6b95a79dbc6d1b05e514131a91768e8040b04"
   * }
   *
   */
    public function sweepSingle(
        $keyImage,
        string $address,
        string $paymentId = '',
        int $mixin = 15,
        int $priority = 2,
        $belowAmount = 0,
        int $unlockTime = 0,
        bool $doNotRelay = false
    ) {
        if (is_array($keyImage)) { // Parameters passed in as object/dictionary
            $params = $keyImage;

            if (array_key_exists('key_image', $params)) {
                $keyImage = $params['key_image'];
            } else {
                throw new Exception('Error: Key image required');
            }
            if (array_key_exists('address', $params)) {
                $address = $params['address'];
            } else {
                throw new Exception('Error: Address required');
            }

            if (array_key_exists('payment_id', $params)) {
                $paymentId = $params['payment_id'];
            }
            if (array_key_exists('mixin', $params)) {
                $mixin = $params['mixin'];
            }
            if (array_key_exists('account_index', $params)) {
                $accountIndex = $params['account_index'];
            }
            if (array_key_exists('priority', $params)) {
                $priority = $params['priority'];
            }
            if (array_key_exists('unlock_time', $params)) {
                $unlockTime = $params['unlock_time'];
            }
            if (array_key_exists('below_amount', $params)) {
                $belowAmount = $params['below_amount'];
            }
            if (array_key_exists('do_not_relay', $params)) {
                $doNotRelay = $params['do_not_relay'];
            }
        }

        $params = array(
            'address' => $address,
            'mixin' => $mixin,
            'get_tx_key' => true,
            'account_index' => $accountIndex,
            'payment_id' => $paymentId,
            'priority' => $priority,
            'below_amount' => $this->_transform($belowAmount),
            'unlock_time' => $unlockTime,
            'do_not_relay' => $doNotRelay ? 1 : 0
        );
        $sweepSingleMethod = $this->runJsonRpc('sweep_single', $params);

        $save = $this->store(); // Save wallet state after transfer

        return $sweepSingleMethod;
    }

  /**
   * Relay a transaction
   *
   * @param  string  $hex  Blob of transaction to relay
   *
   * @return object  // TODO example
   *
   */
    public function relayTx(string $hex)
    {
        $params = array('hex' => $hex);
        $relayTxMethod = $this->runJsonRpc('relay_tx_method', $params);

        $save = $this->store(); // Save wallet state after transaction relay

        return $this->runJsonRpc('relay_tx');
    }

  /**
   * Save wallet
   *
   * @return object  Example:
   *
   */
    public function store()
    {
        return $this->runJsonRpc('store');
    }

  /**
   * Look up incoming payments by payment ID
   *
   * @param  string  $paymentId  Payment ID to look up
   *
   * @return object  Example: {
   *   "payments": [{
   *     "amount": 10350000000000,
   *     "block_height": 994327,
   *     "payment_id": "4279257e0a20608e25dba8744949c9e1caff4fcdafc7d5362ecf14225f3d9030",
   *     "tx_hash": "c391089f5b1b02067acc15294e3629a463412af1f1ed0f354113dd4467e4f6c1",
   *     "unlock_time": 0
   *   }]
   * }
   *
   */
    public function getPayments(string $paymentId)
    {
      // $params = array('payment_id' => $paymentId); // does not work
        $params = [];
        $params['payment_id'] = $paymentId;
        return $this->runJsonRpc('get_payments', $params);
    }

  /**
   * Look up incoming payments by payment ID (or a list of payments IDs) from a given height
   *
   * @param  array   $paymentIds       Array of payment IDs to look up
   * @param  string  $minBlockHeight  Height to begin search
   *
   * @return object  Example: {
   *   "payments": [{
   *     "amount": 10350000000000,
   *     "block_height": 994327,
   *     "payment_id": "4279257e0a20608e25dba8744949c9e1caff4fcdafc7d5362ecf14225f3d9030",
   *     "tx_hash": "c391089f5b1b02067acc15294e3629a463412af1f1ed0f354113dd4467e4f6c1",
   *     "unlock_time": 0
   *   }]
   * }
   *
   */
    public function getBulkPayments($paymentIds, $minBlockHeight)
    {
      // $params = array('payment_ids' => $paymentIds, 'min_block_height' => $minBlockHeight); // does not work
      //$params = array('min_block_height' => $minBlockHeight); // does not work
        $params = [];
        if (!is_array($paymentIds)) {
            throw new Exception('Error: Payment IDs must be array.');
        }
        if ($paymentIds) {
            $params['payment_ids'] = [];
            foreach ($paymentIds as $paymentId) {
                $params['payment_ids'][] = $paymentId;
            }
        }
        return $this->runJsonRpc('get_bulk_payments', $params);
    }

  /**
   * Look up incoming transfers
   *
   * @param  string  $type             Type of transfer to look up; must be 'all', 'available', or 'unavailable' (incoming transfers which have already been spent)
   * @param  int  $accountIndex    Index of account to look up                                                                                                   (optional)
   * @param  string  $subaddrIndices  Comma-separated list of subaddress indices to look up                                                                         (optional)
   *
   * @return object  Example: {
   *   "transfers": [{
   *     "amount": 10000000000000,
   *     "global_index": 711506,
   *     "spent": false,
   *     "tx_hash": "c391089f5b1b02067acc15294e3629a463412af1f1ed0f354113dd4467e4f6c1",
   *     "tx_size": 5870
   *   },{
   *     "amount": 300000000000,
   *     "global_index": 794232,
   *     "spent": false,
   *     "tx_hash": "c391089f5b1b02067acc15294e3629a463412af1f1ed0f354113dd4467e4f6c1",
   *     "tx_size": 5870
   *   },{
   *     "amount": 50000000000,
   *     "global_index": 213659,
   *     "spent": false,
   *     "tx_hash": "c391089f5b1b02067acc15294e3629a463412af1f1ed0f354113dd4467e4f6c1",
   *     "tx_size": 5870
   *   }]
   * }
   */
    public function incomingTransfers(string $type = 'all', int $accountIndex = 0, string $subaddrIndices = '')
    {
        $params = array( 'transfer_type' => $type, 'account_index' => $accountIndex, 'subaddr_indices' => $subaddrIndices);
        return $this->runJsonRpc('incoming_transfers', $params);
    }

  /**
   * Look up a wallet key
   *
   * @param  string  $keyType  Type of key to look up; must be 'view_key', 'spend_key', or 'mnemonic'
   *
   * @return object  Example: {
   *   "key": "7e341d..."
   * }
   *
   */
    public function queryKey(string $keyType)
    {
        $params = array('key_type' => $keyType);
        return $this->runJsonRpc('query_key', $params);
    }

  /**
   * Look up wallet view key
   *
   * @return object  Example: {
   *   "key": "7e341d..."
   * }
   *
   */
    public function viewKey()
    {
        $params = array('key_type' => 'view_key');
        return $this->runJsonRpc('query_key', $params);
    }

  /**
   * Look up wallet spend key
   *
   * @return object  Example: {
   *   "key": "2ab810..."
   * }
   *
   */
    public function spendKey()
    {
        $params = array('key_type' => 'spend_key');
        return $this->runJsonRpc('query_key', $params);
    }

  /**
   * Look up wallet mnemonic seed
   *
   * @return object  Example: {
   *   "key": "2ab810..."
   * }
   *
   */
    public function mnemonic()
    {
        $params = array('key_type' => 'mnemonic');
        return $this->runJsonRpc('query_key', $params);
    }

  /**
   * Create an integrated address from a given payment ID
   *
   * @param  ?string  $paymentId  Payment ID  (optional)
   *
   * @return object  Example: {
   *   "integrated_address": "4BpEv3WrufwXoyJAeEoBaNW56ScQaLXyyQWgxeRL9KgAUhVzkvfiELZV7fCPBuuB2CGuJiWFQjhnhhwiH1FsHYGQQ8H2RRJveAtUeiFs6J"
   * }
   *
   */
    public function makeIntegratedAddress(?string $paymentId = null)
    {
        $params = array('payment_id' => $paymentId);
        return $this->runJsonRpc('make_integrated_address', $params);
    }

  /**
   * Look up the wallet address and payment ID corresponding to an integrated address
   *
   * @param  string  $integratedAddress  Integrated address to split
   *
   * @return object  Example: {
   *   "payment_id": "420fa29b2d9a49f5",
   *   "standard_address": "427ZuEhNJQRXoyJAeEoBaNW56ScQaLXyyQWgxeRL9KgAUhVzkvfiELZV7fCPBuuB2CGuJiWFQjhnhhwiH1FsHYGQGaDsaBA"
   * }
   *
   */
    public function splitIntegratedAddress(string $integratedAddress)
    {
        $params = array('integrated_address' => $integratedAddress);
        return $this->runJsonRpc('split_integrated_address', $params);
    }

  /**
   * Stop the wallet, saving the state
   */
    public function stopWallet()
    {
        return $this->runJsonRpc('stop_wallet');
    }

  /**
   * Rescan the blockchain from scratch
   */
    public function rescanBlockchain()
    {
        return $this->runJsonRpc('rescan_blockchain');
    }

  /**
   * Add notes to transactions
   *
   * @param  array  $txIds  Array of transaction IDs to note
   * @param  array  $notes  Array of notes (strings) to add
   */
    public function setTxNotes($txIds, $notes)
    {
        $params = array( 'txids' => $txIds, 'notes' => $notes);
        return $this->runJsonRpc('set_tx_notes', $params);
    }

  /**
   * Look up transaction note
   *
   * @param  array  $txIds  Array of transaction IDs (strings) to look up
   *
   * @return obect  Example: {
   *   // TODO example
   * }
   *
   */
    public function getTxNotes($txIds)
    {
        $params = array('txids' => $txIds);
        return $this->runJsonRpc('get_tx_notes', $params);
    }

  /**
   * Set a wallet option
   *
   * @param  string  $key    Option to set
   * @param  string  $value  Value to set
   */
    public function setAttribute(string $key, string $value)
    {
        $params = array('key' => $key, 'value' => $value);
        return $this->runJsonRpc('set_attribute', $params);
    }

  /**
   * Look up a wallet option
   *
   * @param  string  $key  Wallet option to query
   *
   * @return object  Example: {
   *   // TODO example
   * }
   *
   */
    public function getAttribute(string $key)
    {
        $params = array('key' => $key);
        return $this->runJsonRpc('get_attribute', $params);
    }

  /**
   * Look up a transaction key
   *
   * @param   string  $txId  Transaction ID to look up
   *
   * @return  object  Example: {
   *   "tx_key": "e8e97866b1606bd87178eada8f995bf96d2af3fec5db0bc570a451ab1d589b0f"
   * }
   *
   */
    public function getTxKey(string $txId)
    {
        $params = array('txid' => $txId);
        return $this->runJsonRpc('get_tx_key', $params);
    }

  /**
   * Check a transaction key
   *
   * @param   string  $address  Address that sent transaction
   * @param   string  $txId     Transaction ID
   * @param   string  $txKey   Transaction key
   *
   * @return  object  Example: {
   *   "confirmations": 1,
   *   "in_pool": ,
   *   "received": 0
   * }
   *
   */
    public function checkTxKey(string $address, string $txId, string $txKey)
    {
        $params = array( 'address' => $address, 'txid' => $txId, 'tx_key' => $txKey);
        return $this->runJsonRpc('check_tx_key', $params);
    }

  /**
   * Create proof (signature) of transaction
   *
   * @param  string  $address  Address that spent funds
   * @param  string  $txid     Transaction ID
   *
   * @return object  Example: {
   *   "signature": "InProofV1Lq4nejMXxMnAdnLeZhHe3FGCmFdnSvzVM1AiGcXjngTRi4hfHPcDL9D4th7KUuvF9ZHnzCDXysNBhfy7gFvUfSbQWiqWtzbs35yUSmtW8orRZzJpYKNjxtzfqGthy1U3puiF"
   * }
   *
   */
    public function getTxProof(string $address, string $txid)
    {
        $params = array('address' => $address, 'txid' => $txid);
        return $this->runJsonRpc('get_tx_proof', $params);
    }

  /**
   * Verify transaction proof
   *
   * @param  string  $address    Address that spent funds
   * @param  string  $txid       Transaction ID
   * @param  string  $signature  Signature (tx_proof)
   *
   * @return object   Example: {
   *   "confirmations": 2,
   *   "good": 1,
   *   "in_pool": ,
   *   "received": 15752471409492,
   * }
   *
   */
    public function checkTxProof(string $address, string $txid, string $signature)
    {
        $params = array('address' => $address, 'txid' => $txid, 'signature' => $signature);
        return $this->runJsonRpc('check_tx_proof', $params);
    }

  /**
   * Create proof of a spend
   *
   * @param  string  $txId  Transaction ID
   *
   * @return object  Example: {
   *   "signature": "SpendProofV1RnP6ywcDQHuQTBzXEMiHKbe5ErzRAjpUB1h4RUMfGPNv4bbR6V7EFyiYkCrURwbbrYWWxa6Kb38ZWWYTQhr2Y1cRHVoDBkK9GzBbikj6c8GWyKbu3RKi9hoYp2fA9zze7UEdeNrYrJ3tkoE6mkR3Lk5HP6X2ixnjhUTG65EzJgfCS4qZ85oGkd17UWgQo6fKRC2GRgisER8HiNwsqZdUTM313RmdUX7AYaTUNyhdhTinVLuaEw83L6hNHANb3aQds5CwdKCUQu4pkt5zn9K66z16QGDAXqL6ttHK6K9TmDHF17SGNQVPHzffENLGUf7MXqS3Pb6eijeYirFDxmisZc1n2mh6d5EW8ugyHGfNvbLEd2vjVPDk8zZYYr7NyJ8JjaHhDmDWeLYy27afXC5HyWgJH5nDyCBptoCxxDnyRuAnNddBnLsZZES399zJBYHkGb197ZJm85TV8SRC6cuYB4MdphsFdvSzygnjFtbAcZWHy62Py3QCTVhrwdUomAkeNByM8Ygc1cg245Se1V2XjaUyXuAFjj8nmDNoZG7VDxaD2GT9dXDaPd5dimCpbeDJEVoJXkeEFsZF85WwNcd67D4s5dWySFyS8RbsEnNA5UmoF3wUstZ2TtsUhiaeXmPwjNvnyLif3ASBmFTDDu2ZEsShLdddiydJcsYFJUrN8L37dyxENJN41RnmEf1FaszBHYW1HW13bUfiSrQ9sLLtqcawHAbZWnq4ZQLkCuomHaXTRNfg63hWzMjdNrQ2wrETxyXEwSRaodLmSVBn5wTFVzJe5LfSFHMx1FY1xf8kgXVGafGcijY2hg1yw8ru9wvyba9kdr16Lxfip5RJGFkiBDANqZCBkgYcKUcTaRc1aSwHEJ5m8umpFwEY2JtakvNMnShjURRA3yr7GDHKkCRTSzguYEgiFXdEiq55d6BXDfMaKNTNZzTdJXYZ9A2j6G9gRXksYKAVSDgfWVpM5FaZNRANvaJRguQyqWRRZ1gQdHgN4DqmQ589GPmStrdfoGEhk1LnfDZVwkhvDoYfiLwk9Z2JvZ4ZF4TojUupFQyvsUb5VPz2KNSzFi5wYp1pqGHKv7psYCCodWdte1waaWgKxDken44AB4k6wg2V8y1vG7Nd4hrfkvV4Y6YBhn6i45jdiQddEo5Hj2866MWNsdpmbuith7gmTmfat77Dh68GrRukSWKetPBLw7Soh2PygGU5zWEtgaX5g79FdGZg"
   * }
   *
   */
    public function getSpendProof(string $txId, ?string $message = null)
    {
        $params = array('txid' => $txId);
        if ($message !== null) {
            $params['message'] = $message;
        }
        return $this->runJsonRpc('get_spend_proof', $params);
    }

  /**
   * Verify spend proof
   *
   * @param  string  $txId       Transaction ID
   * @param  string  $signature  Spend proof to verify
   *
   * @return object  Example: {
   *   "good": 1
   * }
   *
   */
    public function checkSpendProof(string $txId, string $signature, ?string $message = null)
    {
        $params = array( 'txid' => $txId, 'signature' => $signature);
        if ($message !== null) {
            $params['message'] = $message;
        }
        return $this->runJsonRpc('check_spend_proof', $params);
    }

  /**
   * Create proof of reserves
   *
   * @param  string  $accountIndex  Comma-separated list of account indices of which to prove reserves (proves reserve of all accounts if empty)  (optional)
   *
   * @return object   Example: {
   *   "signature": "ReserveProofV11BZ23sBt9sZJeGccf84mzyAmNCP3KzYbE111111111111AjsVgKzau88VxXVGACbYgPVrDGC84vBU61Gmm2eiYxdZULAE4yzBxT1D9epWgCT7qiHFvFMbdChf3CpR2YsZj8CEhp8qDbitsfdy7iBdK6d5pPUiMEwCNsCGDp8AiAc6sLRiuTsLEJcfPYEKe"
   * }
   *
   */
    public function getReserveProof($accountIndex = 'all')
    {
        if ($accountIndex == 'all') {
            $params = array('all' => true);
        } else {
            $params = array('account_index' => $accountIndex);
        }

        return $this->runJsonRpc('get_reserve_proof');
    }

  /**
   * Verify a reserve proof
   *
   * @param  string  $address    Wallet address
   * @param  string  $signature  Reserve proof
   *
   * @return object  Example: {
   *   "good": 1,
   *   "spent": 0,
   *   "total": 0
   * }
   *
   */
    public function checkReserveProof(string $address, string $signature)
    {
        $params = array('address' => $address, 'signature' => $signature);
        return $this->runJsonRpc('check_reserve_proof', $params);
    }

  /**
   * Look up transfers
   *
   * @param  array   $inputTypes      Array of transfer type strings; possible values include 'all', 'in', 'out', 'pending', 'failed', and 'pool'  (optional)
   * @param  int  $accountIndex    Index of account to look up                                                                                  (optional)
   * @param  string  $subaddrIndices  Comma-separated list of subaddress indices to look up                                                        (optional)
   * @param  int  $minHeight       Minimum block height to use when looking up transfers                                                        (optional)
   * @param  int  $maxHeight       Maximum block height to use when looking up transfers                                                        (optional)
   *
   *   OR
   *
   * @param  object  $inputTypes      Array containing any of the options listed above, where only an input types array is required
   *
   * @return object  Example: {
   *   "pool": [{
   *     "amount": 500000000000,
   *     "fee": 0,
   *     "height": 0,
   *     "note": "",
   *     "payment_id": "758d9b225fda7b7f",
   *     "timestamp": 1488312467,
   *     "txid": "da7301d5423efa09fabacb720002e978d114ff2db6a1546f8b820644a1b96208",
   *     "type": "pool"
   *   }]
   * }
   * 4206931337 seems to be "420","69","3","1337". Maybe it could just be null.
   */
    public function getTransfers($inputTypes = ['all'], int $accountIndex = 0, string $subaddrIndices = '', int $minHeight = 0, int $maxHeight = 4206931337)
    {
        if (is_string($inputTypes)) { // If user is using old method
            $params = array( 'subaddr_indices' => $subaddrIndices, 'min_height' => $minHeight, 'max_height' => $maxHeight);
            if (is_bool($accountIndex)) { // If user passed eg. get_transfers('in', true)
                $params['account_index'] = 0;
                $params[$inputTypes]     = $accountIndex; // $params = array($inputType => $inputValue);
            } else { // If user passed eg. get_transfers('in')
                $params['account_index'] = $accountIndex;
                $params[$inputTypes]     = true;
            }
        } else {
            if (is_object($inputTypes) || is_array($inputTypes)) { // Parameters passed in as object/dictionary
                $params = $inputTypes;

                if (array_key_exists('input_types', $params)) {
                    $inputTypes = $params['input_types'];
                } else {
                    $inputTypes = ['all'];
                }
                if (array_key_exists('account_index', $params)) {
                    $accountIndex = $params['account_index'];
                }
                if (array_key_exists('subaddr_indices', $params)) {
                    $subaddrIndices = $params['subaddr_indices'];
                }
                if (array_key_exists('min_height', $params)) {
                    $minHeight = $params['min_height'];
                }
                if (array_key_exists('max_height', $params)) {
                    $maxHeight = $params['max_height'];
                }
            }

            $params = array( 'account_index' => $accountIndex, 'subaddr_indices' => $subaddrIndices, 'min_height' => $minHeight, 'max_height' => $maxHeight);
            for ($i = 0, $iMax = count($inputTypes); $i < $iMax; $i++) {
                $params[$inputTypes[$i]] = true;
            }
        }

        if (array_key_exists('all', $params)) {
            unset($params['all']);
            $params['in'] = true;
            $params['out'] = true;
            $params['pending'] = true;
            $params['failed'] = true;
            $params['pool'] = true;
        }

        if (( $minHeight || $maxHeight) && $maxHeight != 4206931337) {
            $params['filter_by_height'] = true;
        }

        return $this->runJsonRpc('get_transfers', $params);
    }

  /**
   * Look up transaction by transaction ID
   *
   * @param  string  $txid           Transaction ID to look up
   * @param  string  $accountIndex  Index of account to query  (optional)
   *
   * @return object  Example: {
   *   "transfer": {
   *     "amount": 10000000000000,
   *     "fee": 0,
   *     "height": 1316388,
   *     "note": "",
   *     "payment_id": "0000000000000000",
   *     "timestamp": 1495539310,
   *     "txid": "f2d33ba969a09941c6671e6dfe7e9456e5f686eca72c1a94a3e63ac6d7f27baf",
   *     "type": "in"
   *   }
   * }
   *
   */
    public function getTransferByTxid(string $txid, int $accountIndex = 0)
    {
        $params = array('txid' => $txid, 'account_index' => $accountIndex);
        return $this->runJsonRpc('get_transfer_by_txid', $params);
    }

  /**
   * Sign a string
   *
   * @param  string  $data  Data to sign
   *
   * @return object  Example: {
   *   "signature": "SigV1Xp61ZkGguxSCHpkYEVw9eaWfRfSoAf36PCsSCApx4DUrKWHEqM9CdNwjeuhJii6LHDVDFxvTPijFsj3L8NDQp1TV"
   * }
   *
   */
    public function sign($data)
    {
        $params = array('string' => $data);
        return $this->runJsonRpc('sign', $params);
    }

  /**
   * Verify a signature
   *
   * @param  string   $data       Signed data
   * @param  string   $address    Address that signed data
   * @param  string   $signature  Signature to verify
   *
   * @return object  Example: {
   *   "good": true
   * }
   *
   */
    public function verify(string $data, string $address, string $signature)
    {
        $params = array('data' => $data, 'address' => $address, 'signature' => $signature);
        return $this->runJsonRpc('verify', $params);
    }

  /**
   * Export an array of signed key images
   *
   * @return array  Example: {
   *   // TODO example
   * }
   *
   */
    public function exportKeyImages()
    {
        return $this->runJsonRpc('export_key_images');
    }

  /**
   * Import a signed set of key images
   *
   * @param  array   $signedKeyImages  Array of signed key images
   *
   * @return object  Example: {
   *   // TODO example
   *   height: ,
   *   spent: ,
   *   unspent:
   * }
   *
   */
    public function importKeyImages($signedKeyImages)
    {
        $params = array('signed_key_images' => $signedKeyImages);
        return $this->runJsonRpc('import_key_images', $params);
    }

  /**
   * Create a payment URI using the official URI specification
   *
   * @param  string  $address         Address to receive funds
   * @param  string  $amount          Amount of monero to request
   * @param  ?string  $paymentId      Payment ID                   (optional)
   * @param  ?string  $recipientName  Name of recipient            (optional)
   * @param  ?string  $txDescription  Payment description          (optional)
   *
   * @return object  Example: {
   *   // TODO example
   * }
   *
   */
    public function makeUri(string $address, string $amount, ?string $paymentId = null, ?string $recipientName = null, ?string $txDescription = null)
    {
        $params = array( 'address' => $address, 'amount' => $this->_transform($amount), 'payment_id' => $paymentId, 'recipient_name' => $recipientName, 'tx_description' => $txDescription);
        return $this->runJsonRpc('make_uri', $params);
    }

  /**
   * Parse a payment URI
   *
   * @param  string  $uri  Payment URI
   *
   * @return object  Example: {
   *   "uri": {
   *     "address": "44AFFq5kSiGBoZ4NMDwYtN18obc8AemS33DBLWs3H7otXft3XjrpDtQGv7SqSsaBYBb98uNbr2VBBEt7f2wfn3RVGQBEP3A",
   *     "amount": 10,
   *     "payment_id": "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef",
   *     "recipient_name": "Monero Project donation address",
   *     "tx_description": "Testing out the make_uri function"
   *   }
   * }
   *
   */
    public function parseUri(string $uri)
    {
        $params = array('uri' => $uri);
        return $this->runJsonRpc('parse_uri', $params);
    }

  /**
   * Look up address book entries
   *
   * @param  array   $entries  Array of address book entry indices to look up
   *
   * @return object  Example: {
   *   // TODO example
   * }
   *
   */
    public function getAddressBook($entries)
    {
        $params = array('entries' => $entries);
        return $this->runJsonRpc('get_address_book', $params);
    }

  /**
   * Add entry to the address book
   *
   * @param  string  $address      Address to add to address book
   * @param  string  $paymentId   Payment ID to use with address in address book  (optional)
   * @param  string  $description  Description of address                          (optional)
   *
   * @return object  Example: {
   *   // TODO example
   * }
   *
   */
    public function addAddressBook(string $address, string $paymentId, string $description)
    {
        $params = array( 'address' => $address, 'payment_id' => $paymentId, 'description' => $description);
        return $this->runJsonRpc('add_address_book', $params);
    }

  /**
   * Delete an entry from the address book
   *
   * @param  array   $index  Index of the address book entry to remove
   */
    public function deleteAddressBook($index)
    {
        $params = array('index' => $index);
        return $this->runJsonRpc('delete_address_book', $params);
    }

  /**
   * Refresh the wallet after opening
   *
   * @param  ?int  $startHeight  Block height from which to start    (optional)
   *
   * @return object  Example: {
   *   // TODO example
   * }
   *
   */
    public function refresh(?int $startHeight = null)
    {
        $params = array('start_height' => $startHeight);
        return $this->runJsonRpc('refresh', $params);
    }

  /**
   * Rescan the blockchain for spent outputs
   *
   */
    public function rescanSpent()
    {
        return $this->runJsonRpc('rescan_spent');
    }

  /**
   * Start mining
   *
   * @param  int  $threadsCount       Number of threads created for mining.
   * @param  bool $doBackgroundMining Allow to start the miner in smart mining mode – a process of having a throttled miner mine when it otherwise does not cause drawbacks.
   * @param  bool $ignoreBattery      Ignore battery status (for smart mining only).
   *
   * @throws Exception when mining has already started.
   */
    public function startMining(int $threadsCount, bool $doBackgroundMining, bool $ignoreBattery): void
    {
        $params = array(
            'threads_count' => $threadsCount,
            'do_background_mining' => $doBackgroundMining,
            'ignore_battery' => $ignoreBattery,
        );
        $this->runJsonRpc('start_mining', $params);
    }

  /**
   * Stop mining in the Monero daemon.
   */
    public function stopMining(): void
    {
        $this->runJsonRpc('stop_mining');
    }

  /**
   * Look up a list of available languages for your wallet's seed
   *
   * @return object  Example: {
   *   // TODO example
   * }
   *
   */
    public function getLanguages()
    {
        return $this->runJsonRpc('get_languages');
    }

  /**
   * Create a new wallet
   *
   * @see https://github.com/monero-project/monero/blob/2656cdf5056c07684741c4425a051760b97025b0/src/wallet/wallet_rpc_server.cpp#L3285
   *
   * @param  string  $filename  Filename of new wallet to create
   * @param  ?string  $password  Password of new wallet to create
   * @param  string  $language  Language of new wallet to create
   */
    public function createWallet(
        string $filename = 'monero_wallet',
        ?string $password = null,
        string $language = 'English'
    ) {
        $params = array('filename' => $filename, 'password' => $password, 'language' => $language);
        return $this->runJsonRpc('create_wallet', $params);
    }

  /**
   * Open a wallet
   *
   * @param  string  $filename  Filename of wallet to open
   * @param  ?string  $password  Password of wallet to open
   */
    public function openWallet(string $filename = 'monero_wallet', ?string $password = null): void
    {
        $params = array('filename' => $filename, 'password' => $password);
        $this->runJsonRpc('open_wallet', $params);
    }

  /**
   * Check if wallet is multisig
   *
   * @return object  Example: (non-multisignature wallet) {
   *   "multisig": ,
   *   "ready": ,
   *   "threshold": 0,
   *   "total": 0
   * } // TODO multisig wallet example
   *
   */
    public function isMultisig()
    {
        return $this->runJsonRpc('is_multisig');
    }

  /**
   * Create information needed to create a multisignature wallet
   *
   * @return object  Example: {
   *   "multisig_info": "MultisigV1WBnkPKszceUBriuPZ6zoDsU6RYJuzQTiwUqE5gYSAD1yGTz85vqZGetawVvioaZB5cL86kYkVJmKbXvNrvEz7o5kibr7tHtenngGUSK4FgKbKhKSZxVXRYjMRKEdkcbwFBaSbsBZxJFFVYwLUrtGccSihta3F4GJfYzbPMveCFyT53oK"
   * }
   *
   */
    public function prepareMultisig()
    {
        return $this->runJsonRpc('prepare_multisig');
    }

  /**
   * Create a multisignature wallet
   *
   * @param  string  $multisigInfo  Multisignature information (from eg. prepare_multisig)
   * @param  string  $threshold      Threshold required to spend from multisignature wallet
   * @param  string  $password       Passphrase to apply to multisignature wallet
   *
   * @return object  Example: {
   *   // TODO example
   * }
   *
   */
    public function makeMultisig(string $multisigInfo, string $threshold, string $password = '')
    {
        $params = array( 'multisig_info' => $multisigInfo, 'threshold' => $threshold, 'password' => $password);
        return $this->runJsonRpc('make_multisig', $params);
    }

  /**
   * Export multisignature information
   *
   * @return object  Example: {
   *   // TODO example
   * }
   *
   */
    public function exportMultisigInfo()
    {
        return $this->runJsonRpc('export_multisig_info');
    }

  /**
   * Import mutlisignature information
   *
   * @param  string  $info  Multisignature info (from eg. prepare_multisig)
   *
   * @return object   Example: {
   *   // TODO example
   * }
   *
   */
    public function importMultisigInfo(string $info)
    {
        $params = array('info' => $info);
        return $this->runJsonRpc('import_multisig_info', $params);
    }

  /**
   * Finalize a multisignature wallet
   *
   * @param  string  $multisigInfo  Multisignature info (from eg. prepare_multisig)
   * @param  string  $password       Multisignature info (from eg. prepare_multisig)
   *
   * @return object   Example: {
   *   // TODO example
   * }
   *
   */
    public function finalizeMultisig(string $multisigInfo, string $password = '')
    {
        $params = array( 'multisig_info' => $multisigInfo, 'password' => $password);
        return $this->runJsonRpc('finalize_multisig', $params);
    }

  /**
   * Sign a multisignature transaction
   *
   * @param  string  $txDataHex  Blob of transaction to sign
   *
   * @return object  Example: {
   *   // TODO example
   * }
   *
   */
    public function signMultisig(string $txDataHex)
    {
        $params = array('tx_data_hex' => $txDataHex);
        return $this->runJsonRpc('sign_multisig', $params);
    }

  /**
   * Submit (relay) a multisignature transaction
   *
   * @param  string  $txDataHex  Blob of transaction to submit
   *
   * @return object   Example: {
   *   // TODO example
   * }
   *
   */
    public function submitMultisig(string $txDataHex)
    {
        $params = array('tx_data_hex' => $txDataHex);
        return $this->runJsonRpc('submit_multisig', $params);
    }

  /**
   * Validate a wallet address
   *
   * @param  string $address The address to validate.
   *         any_net_type - boolean (Optional); If true, consider addresses belonging to any of the three Monero networks (mainnet, stagenet, and testnet) valid. Otherwise, only consider an address valid if it belongs to the network on which the rpc-wallet's current daemon is running (Defaults to false).
   *         allow_openalias - boolean (Optional); If true, consider OpenAlias-formatted addresses valid (Defaults to false).
   *
   * @return valid - boolean; True if the input address is a valid Monero address.
   *         integrated - boolean; True if the given address is an integrated address.
   *         subaddress - boolean; True if the given address is a subaddress
   *         nettype - string; Specifies which of the three Monero networks (mainnet, stagenet, and testnet) the address belongs to.
   *         openalias_address - boolean; True if the address is OpenAlias-formatted.
   *
   */
    public function validateAddress(string $address, bool $strictNettype = false, bool $allowOpenalias = false)
    {
        $params = array(
        'address' => $address,
        'any_net_type' => $strictNettype,
        'allow_openalias' => $allowOpenalias
        );
        return $this->runJsonRpc('validate_address', $params);
    }

  /**
   * Create a wallet on the RPC server from an address, view key, and (optionally) spend key.
   *
   * @param string $filename is the name of the wallet to create on the RPC server
   * @param string $password is the password encrypt the wallet
   * @param string $address is the address of the wallet to construct
   * @param string $viewKey is the view key of the wallet to construct
   * @param string $spendKey is the spend key of the wallet to construct or null to create a view-only wallet
   * @param string $language is the wallet and mnemonic's language (default = "English")
   * @param int restoreHeight is the block height to restore (i.e. scan the chain) from (default = 0)
   * @param bool saveCurrent specifies if the current RPC wallet should be saved before being closed (default = true)
   *
   */
    public function generateFromKeys(
        string $filename,
        string $password,
        string $address,
        string $viewKey,
        string $spendKey = '',
        string $language = 'English',
        int $restoreHeight = 0,
        bool $saveCurrent = true
    ) {
        $params = array(
            'filename'          => $filename,
            'password'          => $password,
            'address'           => $address,
            'viewkey'           => $viewKey,
            'spendkey'          => $spendKey,
            'language'          => $language,
            'restore_height'    => $restoreHeight,
            'autosave_current'  => $saveCurrent
        );
        return $this->runJsonRpc('generate_from_keys', $params);
    }

  /**
   * Exchange mutlisignature information
   *
   * @param string $password wallet password
   * @param  $multisigInfo info (from eg. prepare_multisig)
   *
   */
    public function exchangeMultisigKeys(string $password, $multisigInfo)
    {
        $params = array(
            'password' => $password,
            'multisig_info' => $multisigInfo
        );
        return $this->runJsonRpc('exchange_multisig_keys', $params);
    }

  /**
   * Obtain information (destination, amount) about a transfer
   *
   * @param  $txInfo txinfo
   *
   */
    public function describeTransfer($txInfo)
    {
        $params = array(
            'multisig_txset' => $txInfo,
        );
        return $this->runJsonRpc('describe_transfer', $params);
    }

  /**
   * Export all outputs in hex format
   */
    public function exportOutputs()
    {
        return $this->runJsonRpc('export_outputs');
    }

  /**
   * Import outputs in hex format
   *
   * @param $outputsDataHex wallet outputs in hex format
   *
   *
   */
    public function importOutputs($outputsDataHex)
    {
        $params = array(
            'outputs_data_hex' => $outputsDataHex,
        );
        return $this->runJsonRpc('import_outputs', $params);
    }

  /**
   * Set whether and how often to automatically refresh the current wallet
   *
   * @param bool $enable Enable or disable automatic refreshing (default = true)
   * @param int $period The period of the wallet refresh cycle (i.e. time between refreshes) in seconds
   *
   */
    public function autoRefresh(bool $enable = true, int $period = 10)
    {
        $params = array(
        'enable' => $enable,
        'period' => $period
        );
        return $this->runJsonRpc('auto_refresh', $params);
    }

  /**
   * Change a wallet password
   *
   * @param string $oldPassword old password or blank
   * @param string $newPassword new password or blank
   */
    public function changeWalletPassword(string $oldPassword = '', string $newPassword = '')
    {
        $params = array(
            'old_password' => $oldPassword,
            'new_password' => $newPassword
        );
        return $this->runJsonRpc('change_wallet_password', $params);
    }

  /**
   * Close wallet
   */
    public function closeWallet()
    {
        return $this->runJsonRpc('close_wallet');
    }

  /**
   * Get RPC version Major & Minor integer-format, where Major is the first 16 bits and Minor the last 16 bits.
   */
    public function getVersion(): Version
    {
        return $this->runJsonRpc('get_version', null, VersionMapper::class);
    }
}
