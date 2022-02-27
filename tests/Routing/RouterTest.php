<?php

namespace App\Tests\Routing;

use App\Routing\Route;
use App\Routing\Router;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class RouterTest extends TestCase
{
  private Router $router;

  public function setUp(): void
  {
    $mockContainer = $this->createMock(ContainerInterface::class);
    $this->router = new Router($mockContainer);
  }

  public function testNoRouteFound()
  {
    $route = $this->router->getRoute('/user/edit/123', 'GET');
    $this->assertNull($route);
  }

  public function testGetRoute()
  {
    $route = new Route(
      '/user/edit/{id}',
      'TestController',
      'testMethod',
      'GET',
      'user_edit',
    );

    $this->router->addRoute($route);

    $route = $this->router->getRoute('/user/edit/123', 'GET');
    $this->assertInstanceOf(Route::class, $route);
  }

  /**
   * @dataProvider routesWithParamsProvider
   */
  public function testUrlMatchesRouteWithParams(string $url, string $route, array $expectedParams)
  {
    $matches = [];
    $match = $this->router->match($url, $route, $matches);

    $this->assertTrue($match);
    foreach ($expectedParams as $getParam) {
      $this->assertArrayHasKey($getParam, $matches);
    }
  }

  public function routesWithParamsProvider()
  {
    yield "User edit with user ID" => ['/user/edit/156', '/user/edit/{id}', ['id']];
    yield "Blog URL with slug & ID" => ['/blog/fake-slug-title/12304', '/blog/{slug}/{id}', ['slug', 'id']];
  }
}
