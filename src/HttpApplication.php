<?php declare(strict_types=1);
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-01-15
 */

namespace StaticServer;

use Microparts\Configuration\ConfigurationInterface;
use Psr\Log\LoggerInterface;
use StaticServer\Middleware\MiddlewareInterface;
use StaticServer\Middleware\QueueMiddlewareInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

final class HttpApplication
{
    /**
     * Cached files.
     *
     * @var array
     */
    private static $files = [];

    /**
     * @var array
     */
    private static $headers = [];

    /**
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var Server
     */
    private $handler;

    /**
     * @var \StaticServer\FileWalker
     */
    private $walker;

    /**
     * @var QueueMiddlewareInterface|MiddlewareInterface[]
     */
    private $middleware;

    /**
     * @var \SplQueue[]
     */
    private $queue;

    /**
     * HttpApplication constructor.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     * @param \Psr\Log\LoggerInterface $logger
     * @param \StaticServer\FileWalker $walker
     */
    public function __construct(ConfigurationInterface $conf, LoggerInterface $logger, FileWalker $walker)
    {
        $this->handler = $this->createServer($conf);
        $this->conf = $conf;
        $this->logger = $logger;
        $this->walker = $walker;
    }

    /**
     * @param $middleware
     * @return void
     */
    public function use(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Run server.
     */
    public function run()
    {
        $this->doLoadInTheMemory();
        $this->registerOnStartListener();
        $this->registerOnRequestListener();

        $this->handler->start();
    }

    /**
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     * @return \Swoole\Http\Server
     */
    private function createServer(ConfigurationInterface $conf): Server
    {
        $server = new Server(
            $conf->get('server.host'),
            $conf->get('server.port'),
            SWOOLE_BASE
        );

        $server->set($conf->get('server.swoole'));

        return $server;
    }

    /**
     * @return void
     */
    private function registerOnStartListener(): void
    {
        $this->handler->on('start', function ($server) {
            $this->logger->info(sprintf('HTTP static server started at %s:%s', $server->host, $server->port));
        });
    }

    /**
     * @return void
     */
    private function registerOnRequestListener(): void
    {
        $this->handler->on('request', function (Request $request, Response $response) {
            $uri = $request->server['request_uri'];

            $response->header('Expires', '0');
            $response->header('Pragma', 'public');
            $response->header('Cache-Control', '"public, must-revalidate, proxy-revalidate"');

            $response->header('software-server', '');
            $response->header('server', '');
            $response->header('x-xss-protection', '1; mode=block');
            $response->header('x-frame-options', 'SAMEORIGIN');
            $response->header('x-content-type', 'nosniff');

            foreach ($this->middleware as $middleware) {
                $middleware->process($request, $response);
            }

            if (isset(self::$files[$uri])) {
                $response->header('Content-Type', self::$headers[$uri] . '; charset=utf-8');
                $response->end(self::$files[$uri]);
            } else {
                $response->header('Content-Type', self::$headers['/'] . '; charset=utf-8');
                $response->end(self::$files['/']);
            }
        });
    }

    /**
     * Load all static files in the memory.
     *
     * @return void
     */
    private function doLoadInTheMemory(): void
    {
        $root = realpath($this->conf->get('server.root'));
        $generator = $this->walker->walk($root);

        /** @var \StaticServer\Transfer $item */
        foreach ($generator as $item) {
            $key = substr($item->getRealpath(), strlen($root));
            self::$files[$key] = $item->getContent();
            self::$headers[$key] = $this->conf->get('server.mimes.' . $item->getExtension(), 'text/plain');

            // Default page.
            if ($item->getFilename() === $this->conf->get('server.index')) {
                self::$files['/'] = $item->getContent();
                self::$headers['/'] = $this->conf->get('server.mimes.html');
            }
        }
    }
}
