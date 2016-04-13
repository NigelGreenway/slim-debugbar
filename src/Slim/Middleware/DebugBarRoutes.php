<?php

namespace Slim\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;

class DebugBarRoutes {

    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function registerRoutes()
    {
        $this->app->group('/_debugbar', function() {
            $this->get('/hello', function(RequestInterface $request, ResponseInterface $response, $args)
            {
                return $response;
            })->setName('debugbar.fonts');
            $this->get('/fonts/{file}', function(RequestInterface $request, ResponseInterface $response, $args)
            {
                $renderer = $this->debugbar->getJavascriptRenderer();
                $file = $args['file'];
                // e.g. $file = fontawesome-webfont.woff?v=4.0.3
                $files = explode('?', $file);
                $file = reset($files);
                $path = $renderer->getBasePath() . '/vendor/font-awesome/fonts/' . $file;
                if (file_exists($path)) {
                    return $response->withHeader('Content-Type', (new \finfo(FILEINFO_MIME))->file($path))->write(file_get_contents($path));
                } else {
                    // font-awesome.css referencing fontawesome-webfont.woff2 but not include in the php-debugbar.
                    // It is not slim-debugbar bug.
                    $this->app->notFound();
                }
            })->setName('debugbar.fonts');
            $this->get('/resources/{file}', function(RequestInterface $request, ResponseInterface $response, $args)
            {
                $renderer = $this->debugbar->getJavascriptRenderer();
                $file = $args['file'];
                $files = explode('.', $file);
                $ext = end($files);
                $content = '';
                if ($ext === 'css') {
                    foreach ($renderer->getAssets('css') as $file) {
                        $content .= file_get_contents($file) . "\n";
                    }
                    return $response->withHeader('Content-Type', 'text/css')->write($content);
                } elseif ($ext === 'js') {
                    foreach ($renderer->getAssets('js') as $file) {
                        $content .= file_get_contents($file) . "\n";
                    }
                    return $response->withHeader('Content-Type', 'text/javascript')->write($content);
                }
            })->setName('debugbar.resources');
            $this->get('/openhandler', function()
            {
                $openHandler = new OpenHandler($this->debugbar);
                $data = $openHandler->handle($request = null, $echo = false, $sendHeader = false);
                $this->app->response->header('Content-Type', 'application/json');
                $this->app->response->setBody($data);
            })->setName('debugbar.openhandler');
        });
    }
}