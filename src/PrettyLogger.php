<?php declare(strict_types=1);

namespace StaticServer;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

final class PrettyLogger
{
    public const CHANNEL = 'Server';

    /**
     * Create Monolog logger without fucking brackets -> [] []  [] []  [] []  [] []  [] []
     * if context and extra is empty.
     *
     * @param string $level
     * @param string $channel
     * @return \Monolog\Logger
     */
    public static function create(string $level = LogLevel::INFO, string $channel = self::CHANNEL): Logger
    {
        $logger = new Logger($channel);
        $handler = new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, $level);
        $formatter = new LineFormatter('[%datetime%] %channel%.%level_name%: %message% %context% %extra%');
        $formatter->ignoreEmptyContextAndExtra();

        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        return $logger;
    }
}
