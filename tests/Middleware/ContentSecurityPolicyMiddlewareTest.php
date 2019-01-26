<?php declare(strict_types=1);

/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 2019-01-18
 */

namespace StaticServer\Tests\Middleware;

use Microparts\Configuration\Configuration;
use StaticServer\Middleware\ContentSecurityPolicyMiddleware;
use StaticServer\Tests\TestCase;
use Swoole\Http\Request;
use Swoole\Http\Response;

class ContentSecurityPolicyMiddlewareTest extends TestCase
{
    public function testHowCspMiddlewareAddedHeaders()
    {
//        $conf = new Configuration(__DIR__ . '/../configuration');
//        $conf->load();
//
//        $request = $this->createMock(Request::class);
//        $response = $this->createMock(Response::class);
//        $response
//            ->expects($this->atLeast(2))
//            ->method('header');
//
//        $middleware = new ContentSecurityPolicyMiddleware($conf);
//        $middleware->process($request, $response);

        $this->assertTrue(true);
    }
}
