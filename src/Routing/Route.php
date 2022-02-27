<?php

namespace App\Routing;

class Route extends AbstractRoute
{
  private array $getParams = [];
  private string $controller;
  private string $method;

  public function __construct(
    string $path,
    string $controller,
    string $method,
    string $httpMethod = "GET",
    string $name = "default",
  ) {
    parent::__construct($path, $httpMethod, $name);
    $this->controller = $controller;
    $this->method = $method;
  }

  public function getRegex(): string
  {
    // URL parameters into capturing regex parts
    $routeRegex = preg_replace("/\{(\w+)\}/", '(?P<${1}>.+)', $this->getPath());
    // Slashes escaping, add regex delimiters
    $routeRegex = "/^" . str_replace("/", "\/", $routeRegex) . "$/";

    return $routeRegex;
  }

  public function getGetParams(): array
  {
    return $this->getParams;
  }

  public function setGetParams(array $getParams): self
  {
    $this->getParams = $getParams;

    return $this;
  }

  public function getController(): string
  {
    return $this->controller;
  }

  public function setController(string $controller): self
  {
    $this->controller = $controller;

    return $this;
  }

  public function getMethod(): string
  {
    return $this->method;
  }

  public function setMethod(string $method): self
  {
    $this->method = $method;

    return $this;
  }
}
