<?php declare(strict_types=1);
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-01-18
 */

namespace StaticServer\Tests;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Psr\Log\LogLevel;
use StaticServer\PrettyLogger;

class PrettyLoggerTest extends TestCase
{
    public function testInitialization()
    {
        $logger = PrettyLogger::create(LogLevel::INFO);
        $handler = $logger->getHandlers()[0];

        $this->assertSame('Server', $logger->getName());
        $this->assertInstanceOf(ErrorLogHandler::class, $handler);
        $this->assertInstanceOf(LineFormatter::class, $handler->getFormatter());
    }
}
