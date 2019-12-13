<?php declare(strict_types=1);

namespace StaticServer\Console;

use StaticServer\Reload;
use StaticServer\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ReloadServerCommand
 *
 * @codeCoverageIgnore
 * @package StaticServer\Console
 */
class ReloadServerCommand extends Command
{

    protected function configure(): void
    {
        $this
            ->setName('reload')
            ->setDescription('Reload server')
            ->setHelp('Example of usage: `server reload`');
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
        Server::fromGlobals()->reload();

        return 0;
    }
}
