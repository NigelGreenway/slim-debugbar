<?php

namespace DebugBar\DataCollector;

use Slim\App;
use Slim\Http\Request;
use Slim\Route;

class SlimRouteCollector extends ConfigCollector
{
    /**
     * @var Request
     */
    protected $request;

    /** @var Route $route */
    protected $route;

    /**
     * SlimRouteCollector constructor.
     * @param Request $request
     * @param Route|null $route
     */
    public function __construct(Request $request, Route $route = null)
    {
        $this->request = $request;
        $this->route = $route;
        $this->setData($this->getRouteInfo());
    }

    public function getName()
    {
        return 'route';
    }

    public function getRouteInfo()
    {
        $route = $this->route;
        $method = $this->request->getMethod();
        $path = $this->request->getUri()->getPath();
        $uri = $method . ' ' . $path;
        return [
            'id' => $route->getIdentifier() ?: '-',
            'uri' => $uri,
            'pattern' => $route->getPattern(),
            'arguments' => $route->getArguments() ?: '-',
            'name' => $route->getName() ?: '-',
            'groups' => $route->getGroups() ?: '-',
        ];
    }

    public function getWidgets()
    {
        $name = $this->getName();
        $data = parent::getWidgets();
        $data['currentroute'] = [
            'icon' => 'share',
            'tooltip' => 'Route',
            'map' => "$name.uri",
            'default' => '{}',
        ];
        return $data;
    }
}
