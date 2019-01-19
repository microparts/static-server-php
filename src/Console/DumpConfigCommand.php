<?php declare(strict_types=1);

namespace StaticServer\Console;

use StaticServer\SimpleInit;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpConfigCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('dump')
            ->setDescription('Dump loaded configuration')
            ->setHelp('Example of usage: ./static-server dump');
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
        $string = SimpleInit::silent()->dump();

        $output->writeln($string);
    }
}
