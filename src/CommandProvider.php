<?php

declare(strict_types=1);

namespace Jubayed\Composer;

use Jubayed\Composer\Command\DumpAutoloadCommand;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

class CommandProvider implements CommandProviderCapability
{
    public function getCommands()
    {
        return [
            new DumpAutoloadCommand(),
        ];
    }
}