<?php declare(strict_types=1);

namespace StaticServer\Tests\Console;

use StaticServer\Console\DumpConfigCommand;
use StaticServer\Tests\TestCase;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

class DumpConfigCommandTest extends TestCase
{
    public function testDump()
    {
        $this->setOutputCallback(function () {
            $input = $this->createMock(Input::class);
            $output = $this->createMock(Output::class);

            $command = new DumpConfigCommand();
            $exit = $command->run($input, $output);
            $this->assertEquals(0, $exit);
        });
    }
}
