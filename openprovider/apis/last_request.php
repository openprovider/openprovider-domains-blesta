<?php

class LastRequest
{
    /**
     * @var string|null last request command
     */
    private ?string $cmd;

    /**
     * @var array|null last request arguments
     */
    private ?array $args;

    /**
     * @param string $cmd
     */
    public function setCommand(string $cmd): void
    {
        $this->cmd = $cmd;
    }

    /**
     * @param array $args
     */
    public function setArgs(array $args): void
    {
        $this->args = $args;
    }

    /**
     * @return string|null
     */
    public function getCommand(): ?string
    {
        return $this->cmd;
    }

    /**
     * @return array|null
     */
    public function getArgs(): ?array
    {
        return $this->args;
    }
}
