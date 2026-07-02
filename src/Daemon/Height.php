<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

interface Height
{
    public function getHash(): string;

    public function getHeight(): int;
}
