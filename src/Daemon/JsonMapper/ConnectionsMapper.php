<?php

namespace BrianHenryIE\MoneroRpc\Daemon\JsonMapper;

use BrianHenryIE\MoneroRpc\Daemon\Connection;
use BrianHenryIE\MoneroRpc\Daemon\Connections;

class ConnectionsMapper implements Connections
{
    use ResponseBaseTrait;

    /**
     * @var ConnectionMapper[]
     */
    protected array $connections;

    /**
     * @return Connection[]
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * @param Connection[] $connections
     */
    public function setConnections(array $connections): void
    {
        $this->connections = $connections;
    }
}
