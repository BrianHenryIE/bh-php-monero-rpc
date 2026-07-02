<?php

namespace BrianHenryIE\MoneroRpc\Wallet\JsonMapper;

use BrianHenryIE\MoneroRpc\Wallet\Refresh;
use BrianHenryIE\MoneroRpc\Wallet\RefreshResult;

class RefreshMapper implements Refresh
{
    protected RefreshResult $result;

	public function getRefreshResult(): RefreshResult {
		return $this->result;
	}
	public function setRefreshResult( RefreshResult $result): void {
		$this->result = $result;
	}
}
