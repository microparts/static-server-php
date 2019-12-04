<?php declare(strict_types=1);

namespace StaticServer\Console;

use StaticServer\Server;

class SignalHandler
{
    /**
     * @param int $sigNumber
     */
    public function handle(int $sigNumber)
    {
        switch ($sigNumber) {
            case SIGTERM:
            case SIGINT:
            case SIGQUIT:
            case SIGSTOP:
                Server::new()->stop();
                exit;
                break;
            case SIGUSR1:
                Server::new()->reload();
                break;
            default:
                Server::new()->stop();
        }
    }
}
