<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

interface Connections extends ResponseBase
{
    /**
     * @return Connection[]
     */
    public function getConnections(): array;
}
