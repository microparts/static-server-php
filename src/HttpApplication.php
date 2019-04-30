<?php declare(strict_types=1);

namespace StaticServer;

use InvalidArgumentException;
use Microparts\Configuration\ConfigurationInterface;
use Psr\Log\LoggerInterface;
use StaticServer\Middleware\MiddlewareInterface;
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
    private static $mimes = [];

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
     * @var MiddlewareInterface[]
     */
    private $middleware;

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
        $this->conf    = $conf;
        $this->logger  = $logger;
        $this->walker  = $walker;
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
     * Dry run without start of server.
     */
    public function dryRun()
    {
        $this->doLoadInTheMemory();
        $this->registerOnStartListener();
        $this->registerOnRequestListener();
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
            SWOOLE_PROCESS
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
        $headers = $this->getHeadersValues();

        $this->handler->on('request', function (Request $request, Response $response) use ($headers) {
            $uri = $request->server['request_uri'];

            $response->header('Expires', $headers['expires']);
            $response->header('Pragma', $headers['pragma']);
            $response->header('Cache-Control', $headers['cache_control']);

            $response->header('software-server', '');
            $response->header('server', '');
            $response->header('x-xss-protection', '1; mode=block');
            $response->header('x-frame-options', $headers['frame_options']);
            $response->header('x-content-type', 'nosniff');
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('X-UA-Compatible', 'IE=edge');
            $response->header('Referrer-Policy', $headers['referer_policy']);
            $response->header('Feature-Policy', $headers['feature_policy']);
            $response->header('content-security-policy', $headers['csp']);
            $response->header('strict-transport-security', 'max-age=31536000; includeSubDomains; preload');

//            foreach ($this->middleware as $middleware) {
//                $middleware->process($request, $response);
//            }

            // if passed URI is file from fs, return it.
            if (isset(self::$files[$uri])) {
                $response->header('Content-Type', self::$mimes[$uri] . '; charset=utf-8');
                $response->end(self::$files[$uri]);
                // if file not found in memory and it has extension, return 404
            } elseif (pathinfo($uri, PATHINFO_EXTENSION)) {
                $response->status(404);
                $response->end('404 not found');
            } else {
                // otherwise to forward the request to index file to handle it within javascript router.
                $response->header('Content-Type', self::$mimes['/'] . '; charset=utf-8');
                $response->end(self::$files['/']);
            }
        });
    }

    /**
     * @return array
     */
    private function getHeadersValues(): array
    {
        $template = [
            '{{next_year}}' => (int) date('Y') + 1,
        ];

        $k = array_keys($template);
        $v = array_values($template);

        $array = [];
        foreach ($this->conf->get('server.headers', []) as $header => $value) {
            $array[$header] = join(';', (array) str_replace($k, $v, $value));
        }

        return $array;
    }

    /**
     * Load all static files in the memory.
     *
     * @return void
     */
    private function doLoadInTheMemory(): void
    {
        $root = $this->getRootPath();
        $generator = $this->walker->walk($root);

        /** @var \StaticServer\Transfer $item */
        foreach ($generator as $item) {
            $key = substr($item->getRealpath(), strlen($root));
            self::$files[$key] = $item->getContent();
            self::$mimes[$key] = $this->conf->get('server.mimes.' . $item->getExtension(), 'text/plain');

            // Default page.
            if ($item->getFilename() === $this->conf->get('server.index')) {
                self::$files['/'] = $item->getContent();
                self::$mimes['/'] = $this->conf->get('server.mimes.html');
            }
        }
    }

    /**
     * @return string
     */
    private function getRootPath(): string
    {
        $root = realpath($this->conf->get('server.root'));

        // If it exist, check if it's a directory
        if($root !== false && is_dir($root)) {
            return $root;
        }

        throw new InvalidArgumentException('Root server directory not found or it is not directory.');
    }
}
