<?php declare(strict_types=1);

namespace StaticServer\Console;

use StaticServer\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunServerCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Run server')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run command without server starting.')
            ->setHelp('Example of usage: `server run`. If u want to change starting host or port, please change the __server_*.yaml configuration files.');
    }

    /**
     * Execute command, captain.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Server::fromGlobals()->run($input->getOption('dry-run'));
    }
}
