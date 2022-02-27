<?php

namespace App\Routing;

use App\Routing\Attribute\Route as RouteAttribute;
use App\Utils\Filesystem;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;

class Router
{
  /** @var Route[] */
  private $routes = [];
  private ContainerInterface $container;
  private const CONTROLLERS_NAMESPACE = "App\\Controller\\";
  private const CONTROLLERS_DIR = __DIR__ . "/../Controller";

  public function __construct(ContainerInterface $container)
  {
    $this->container = $container;
  }

  /**
   * Add a route into the router internal array
   *
   * @param string $name
   * @param string $url
   * @param string $httpMethod
   * @param string $controller Controller class
   * @param string $method
   * @return self
   */
  public function addRoute(Route $route): self
  {
    $this->routes[] = $route;

    return $this;
  }

  /**
   * Executes a route based on provided URI and HTTP method.
   *
   * @param string $uri
   * @param string $httpMethod
   * @return void
   * @throws RouteNotFoundException
   */
  public function execute(string $uri, string $httpMethod)
  {
    $route = $this->getRoute($uri, $httpMethod);

    if ($route === null) {
      throw new RouteNotFoundException();
    }

    $controllerName = $route->getController();
    $constructorParams = $this->getMethodServiceParams($controllerName, '__construct');
    $controller = new $controllerName(...$constructorParams);

    $method = $route->getMethod();
    $servicesParams = $this->getMethodServiceParams($controllerName, $method);
    $getParams = $route->getGetParams();

    call_user_func_array(
      [$controller, $method],
      array_merge($servicesParams, $getParams)
    );
  }

  /**
   * Get a route. Returns null if not found
   *
   * @param string $uri
   * @param string $httpMethod
   * @return Route|null
   */
  public function getRoute(string $uri, string $httpMethod): ?Route
  {
    foreach ($this->routes as $route) {
      $matches = [];
      if ($this->match($uri, $route->getPath(), $matches) && $route->getHttpMethod() === $httpMethod) {
        $matches = array_filter($matches, fn ($key) => !is_int($key), ARRAY_FILTER_USE_KEY);

        $route->setGetParams($matches);
        return $route;
      }
    }

    return null;
  }

  public function match(string $url, string $routeUrl, array &$matches): bool
  {
    // URL parameters into capturing regex parts
    $routeRegex = preg_replace("/\{(\w+)\}/", '(?P<${1}>.+)', $routeUrl);
    // Slashes escaping, add regex delimiters
    $routeRegex = "/^" . str_replace("/", "\/", $routeRegex) . "$/";

    return preg_match($routeRegex, $url, $matches) === 1;
  }

  /**
   * Resolve method's parameters from the service container
   *
   * @param string $controller name of controller
   * @param string $method name of method
   * @return array
   */
  private function getMethodServiceParams(string $controller, string $method): array
  {
    $methodInfos = new ReflectionMethod($controller . '::' . $method);
    $methodParameters = $methodInfos->getParameters();

    $params = [];

    foreach ($methodParameters as $param) {
      $paramName = $param->getName();
      $paramType = $param->getType()->getName();

      if ($this->container->has($paramType)) {
        $params[$paramName] = $this->container->get($paramType);
      }
    }

    return $params;
  }

  public function registerRoutes(): void
  {
    $classNames = Filesystem::getClassNames(self::CONTROLLERS_DIR);

    foreach ($classNames as $class) {
      $this->registerRoute($class);
    }
  }

  public function registerRoute(string $className): void
  {
    $fqcn = self::CONTROLLERS_NAMESPACE . $className;
    $reflection = new ReflectionClass($fqcn);

    if ($reflection->isAbstract()) {
      return;
    }

    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

    foreach ($methods as $method) {
      $attributes = $method->getAttributes(RouteAttribute::class);

      foreach ($attributes as $attribute) {
        /** @var RouteAttribute */
        $route = $attribute->newInstance();

        $this->addRoute(new Route(
          $route->getPath(),
          $fqcn,
          $method->getName(),
          $route->getHttpMethod(),
          $route->getName()
        ));
      }
    }
  }
}
