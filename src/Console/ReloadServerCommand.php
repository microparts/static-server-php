<?php declare(strict_types=1);

namespace StaticServer\Console;

use Microparts\Configuration\Configuration;
use Microparts\Logger\Logger;
use StaticServer\Reload;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReloadServerCommand extends Command
{

    protected function configure()
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
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conf = Configuration::auto();
        $conf->setLogger(Logger::default('Reload'));
        $conf->load();

        $reload = new Reload($conf);
        $code = $reload->send();

        if ($code !== 0) {
            exit($code);
        }

        $output->writeln("\n<comment>Server reloaded</comment>");
    }
}
