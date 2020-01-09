<?php declare(strict_types=1);

namespace StaticServer\Console;

use StaticServer\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StopServerCommand extends Command
{

    protected function configure(): void
    {
        $this
            ->setName('stop')
            ->setDescription('Stop server')
            ->setHelp('Example of usage: `server stop`.');
    }

    /**
     * Execute command, captain.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Throwable
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Server::fromGlobals()->stop();

        return 0;
    }
}
