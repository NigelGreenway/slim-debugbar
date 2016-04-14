<?php

namespace Slim\Middleware;

use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\HttpDriverInterface;
use DebugBar\OpenHandler;
use DebugBar\SlimDebugBar;
use DebugBar\SlimHttpDriver;
use DebugBar\Storage\StorageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Route;

final class DebugBar
{
    /**
     * Slim Application instance
     *
     * @var \Slim\App
     */
    protected $app;

    /**
     * Debugbar instance
     *
     * @var \DebugBar\SlimDebugBar
     */
    protected $debugbar;

    /**
     * @var HttpDriverInterface
     */
    protected $httpDriver;

    public function __construct(HttpDriverInterface $HttpDriver = null)
    {
        $this->httpDriver = $HttpDriver;
        $this->debugbar = new SlimDebugBar();
    }

    /**
     * @param DataCollectorInterface $collector
     * @throws \DebugBar\DebugBarException
     */
    public function addCollector(DataCollectorInterface $collector)
    {
        $this->debugbar->addCollector($collector);
    }

    /**
     * @return SlimDebugBar
     */
    public function getDebugBar()
    {
        return $this->debugbar;
    }

    /**
     * @param \DebugBar\DebugBar $debugbar
     */
    public function setDebugBar(\DebugBar\DebugBar $debugbar)
    {
        $this->debugbar = $debugbar;
    }

    public function __invoke(Request $request, ResponseInterface $response, callable $next)
    {
        $this->app   = $next;
        $this->prepareDebugBar();

        $httpDriver = $this->httpDriver ?: new SlimHttpDriver($response);
        $this->debugbar->setHttpDriver($httpDriver);

        $response = $next($request, $response);

        $this->prepareAfterRouteDebugBar($response, $request, $request->getAttribute('route'));

        if ($request->isXhr()) {
            if ($this->debugbar->getStorage()) {
                $this->debugbar->sendDataInHeaders($useOpenHandler = true);
            }
            return $response;
        }

        if ( ! $this->isModifiable($response)) {
            return $response;
        }

        if (! $this->isAsset($request)) {
            $response->write($this->getDebugHtml($request));
        }

        return $response;
    }

    public function isModifiable(ResponseInterface $response)
    {
        if ($response->isRedirect()) {
            if ($this->debugbar->getHttpDriver()->isSessionStarted()) {
                $this->debugbar->stackData();
            }
            return false;
        }

        if ( ! $this->isHtmlResponse($response)) {
            return false;
        }

        return true;
    }

    public function isAsset(Request $request)
    {
        $route = $request->getAttribute('route', null);

        if ($route !== null) {
            if (strstr($route->getName(), 'debugbar') !== false) {
                return true;
            }
        }

        return false;
    }

    public function isHtmlResponse(ResponseInterface $response)
    {
        $content_type = $response->getHeader('Content-Type')[0];

        return (stripos($content_type, 'html') !== false);
    }

    public function getDebugHtml(RequestInterface $request)
    {
        $renderer = $this->debugbar->getJavascriptRenderer();
        if ($this->debugbar->getStorage()) {
            $renderer->setOpenHandlerUrl($this->app->getConter()->get('router')->urlFor('debugbar.openhandler'));
        }

        $html = $this->getAssetsHtml($request);
        if ($renderer->isJqueryNoConflictEnabled()) {
            $html .= "\n" . '<script type="text/javascript">jQuery.noConflict(true);</script>';
        }

        return $html . "\n" . $renderer->render();
    }

    public function getAssetsHtml(RequestInterface $request)
    {
        $root = $request->getServerParams()['SCRIPT_NAME'];
        return '<script type="text/javascript" src="' . $root . '/_debugbar/resources/dump.js"></script>' .
        '<link rel="stylesheet" type="text/css" href="' . $root . '/_debugbar/resources/dump.css">';
    }

    protected function prepareDebugBar()
    {
        if ($this->debugbar instanceof SlimDebugBar) {
            $this->debugbar->initCollectorsBeforeRoute($this->app);
        }
        $storage = $this->app->getContainer()->get('settings')['debugbar.storage'];
        if ($storage instanceof StorageInterface) {
            $this->debugbar->setStorage($storage);
        }
        // add debugbar to Slim IoC container
        $container = $this->app->getContainer();
        $container['debugbar'] = function($this) {
            return $this->debugbar;
        };
    }

    protected function prepareAfterRouteDebugBar(Response $response, Request $request, Route $route = null)
    {
        if ($this->debugbar instanceof SlimDebugBar) {
            $this->debugbar->initCollectorsAfterRoute($this->app, $response, $request, $route);
        }
    }

}