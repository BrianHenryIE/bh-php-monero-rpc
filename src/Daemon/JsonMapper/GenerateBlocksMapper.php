<?php

namespace BrianHenryIE\MoneroRpc\Daemon\JsonMapper;

use BrianHenryIE\MoneroRpc\Daemon\Block;
use BrianHenryIE\MoneroRpc\Daemon\BlockHeader;
use BrianHenryIE\MoneroRpc\Daemon\GenerateBlocks;

class GenerateBlocksMapper implements GenerateBlocks
{
    use ResponseBaseTrait;

    /**
     * @param string[] $blocks
     */
    public function __construct(
        protected array $blocks,
        protected int $height
    ) {
    }

    /**
     * @return string[]
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @param string[] $blocks
     */
    public function setBlocks(array $blocks): void
    {
        $this->blocks = $blocks;
    }

    public function setHeight(int $height): void
    {
        $this->height = $height;
    }
}
