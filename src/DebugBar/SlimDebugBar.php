<?php namespace DebugBar;

use Slim\Slim;
use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\SlimInfoCollector;
use DebugBar\DataCollector\SlimLogCollector;
use DebugBar\DataCollector\SlimViewCollector;
use DebugBar\DataCollector\TimeDataCollector;

class SlimDebugBar extends DebugBar
{
    public function __construct(Slim $slim)
    {
        $this->addCollector(new SlimLogCollector($slim));
        $this->addCollector(new SlimInfoCollector($slim));
        $slim->hook('slim.after.router', function() use ($slim)
        {
            // collect latest settings
            $setting = $this->prepareRenderData($slim->container['settings']);
            $this->addCollector(new ConfigCollector($setting));
        });
        $slim->hook('slim.after.router', function() use ($slim)
        {
            $data = $this->prepareRenderData($slim->view->all());
            $this->addCollector(new SlimViewCollector($data));
        });

        $this->addCollector(new PhpInfoCollector());
        $this->addCollector(new RequestDataCollector());
        $this->addCollector(new TimeDataCollector());
        $this->addCollector(new MemoryCollector());
    }

    protected function prepareRenderData(array $data = [])
    {
        $tmp = [];
        foreach ($data as $key => $val) {
            if (is_object($val)) {
                if (method_exists($val, 'toArray')) {
                    $val = $val->toArray();
                } else {
                    $val = "Object (". get_class($val) .")";
                }
            }
            $tmp[$key] = $val;
        }
        return $tmp;
    }
}