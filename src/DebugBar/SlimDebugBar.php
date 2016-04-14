<?php namespace DebugBar;

use DebugBar\Bridge\Twig\TraceableTwigEnvironment;
use DebugBar\Bridge\Twig\TwigCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use Slim\App;
use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DataCollector\SlimEnvCollector;
use DebugBar\DataCollector\SlimLogCollector;
use DebugBar\DataCollector\SlimResponseCollector;
use DebugBar\DataCollector\SlimRouteCollector;
use DebugBar\DataCollector\SlimViewCollector;

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

    public function initCollectorsBeforeRoute(App $slim)
    {
        $this->addCollector(new SlimEnvCollector($slim));
    }

    public function initCollectorsAfterRoute(App $slim, $response, $request, $route = null)
    {
        $container = $slim->getContainer();

        $setting = $this->prepareRenderData($container->get('settings')->all());

        $this->addCollector(new ConfigCollector($setting));

        $this->addCollector(new SlimResponseCollector($response));

        $this->addCollector(new SlimRouteCollector($request, $route));

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