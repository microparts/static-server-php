<?php declare(strict_types=1);

namespace StaticServer\Console;

use StaticServer\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpConfigCommand extends Command
{

    protected function configure(): void
    {
        $this
            ->setName('dump')
            ->setDescription('Dump loaded configuration')
            ->setHelp('Example of usage: `server dump`');
    }

    /**
     * Execute command, captain.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Server::fromGlobals()->dump();

        return 0;
    }
}
