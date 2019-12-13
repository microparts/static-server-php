<?php declare(strict_types=1);

namespace StaticServer\Console;

use StaticServer\Server;

class SignalHandler
{
    /**
     * @param int $sigNumber
     * @throws \Throwable
     */
    public function handle(int $sigNumber): void
    {
        switch ($sigNumber) {
            case SIGTERM:
            case SIGINT:
            case SIGQUIT:
            case SIGSTOP:
                Server::fromGlobals()->stop();
                exit;
            case SIGUSR1:
                Server::fromGlobals()->reload();
                break;
            default:
                Server::fromGlobals()->stop();
        }
    }
}
