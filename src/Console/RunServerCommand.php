<?php declare(strict_types=1);

namespace StaticServer\Console;

use StaticServer\SimpleInit;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunServerCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Run server')
            ->setHelp('Example of usage: ./static-server run. If u want to change starting host or port, please change the server.yaml configuration file.');
    }

    /**
     * Execute command, captain.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        SimpleInit::new()->run();
    }
}
