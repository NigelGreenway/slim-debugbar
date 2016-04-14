<?php

namespace DebugBar;

use DebugBar\Bridge\MonologCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use Slim\App;
use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DataCollector\SlimEnvCollector;
use DebugBar\DataCollector\SlimResponseCollector;
use DebugBar\DataCollector\SlimRouteCollector;

class SlimDebugBar extends DebugBar
{
    public function __construct()
    {
        $this->addCollector(new TimeDataCollector());
        $collector = $this->getCollector('time');
        $collector->startMeasure('application', 'Application');

        $this->addCollector(new MessagesCollector());
        $this->addCollector(new RequestDataCollector());
        $this->addCollector(new PhpInfoCollector());
        $this->addCollector(new MemoryCollector());
    }

    public function initCollectorsBeforeRoute(App $app, $config)
    {
        if ($this->isLoggerEnabled($config)) {
            $logger = $app->getContainer()->get($config['logger']);
            $this->addCollector(new MonologCollector($logger));
        }

        $this->addCollector(new SlimEnvCollector($app));
    }

    public function initCollectorsAfterRoute(App $app, $response, $request, $route = null)
    {
        $container = $app->getContainer();

        $setting = $this->prepareRenderData($container->get('settings')->all());

        $this->addCollector(new ConfigCollector($setting));

        $this->addCollector(new SlimResponseCollector($response));

        $this->addCollector(new SlimRouteCollector($request, $route));

    }

    protected function isLoggerEnabled($config)
    {
        return array_key_exists('logger', $config);
    }

    protected function prepareRenderData(array $data = [])
    {
        $tmp = [];
        foreach ($data as $key => $val) {
            if (is_object($val)) {
                $val = "Object (". get_class($val) .")";
            }
            $tmp[$key] = $val;
        }
        return $tmp;
    }
}