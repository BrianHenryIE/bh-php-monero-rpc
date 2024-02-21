<?php

namespace BrianHenryIE\MoneroRpc\Daemon;

interface GenerateBlocks extends ResponseBase
{
    /**
     * @return string[]
     */
    public function getBlocks(): array;

    public function getHeight(): int;
}
