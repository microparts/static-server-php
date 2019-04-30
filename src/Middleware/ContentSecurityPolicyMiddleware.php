<?php declare(strict_types=1);

namespace StaticServer\Middleware;

use Microparts\Configuration\ConfigurationInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class ContentSecurityPolicyMiddleware implements MiddlewareInterface
{
    /**
     * @var \Microparts\Configuration\ConfigurationInterface
     */
    private $conf;

    /**
     * ContentSecurityPolicyMiddleware constructor.
     *
     * @param \Microparts\Configuration\ConfigurationInterface $conf
     */
    public function __construct(ConfigurationInterface $conf)
    {
        $this->conf = $conf;
    }

    /**
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     * @return mixed
     */
    public function process(Request $request, Response $response): void
    {
        foreach ($this->conf->get('content_security_policy', []) as $value) {
            $response->header('content-security-policy', $value);
        }
    }
}
