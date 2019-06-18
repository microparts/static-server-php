<?php declare(strict_types=1);

namespace StaticServer\Tests\Console;

use StaticServer\Console\RunServerCommand;
use StaticServer\Tests\TestCase;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

class RunServerCommandTest extends TestCase
{
    public function testServerInitializationThroughConsoleCommand()
    {
        $this->setOutputCallback(function () {
            $path = __DIR__ . '/../../tests/configuration';
            putenv('STAGE=tests');
            putenv("CONFIG_PATH=$path");

            $input = $this->createMock(Input::class);
            $input->expects($this->any())
                  ->method('getOption')
                  ->will($this->returnValueMap([
                      ['dry-run', true],
                      ['silent', true]
                  ]));

            $output = $this->createMock(Output::class);

            $command = new RunServerCommand();
            $exit = $command->run($input, $output);
            $this->assertEquals(0, $exit);
        });
    }
}
