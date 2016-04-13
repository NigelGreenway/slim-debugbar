<?php namespace DebugBar\DataCollector;

use Slim\App;

class SlimEnvCollector extends DataCollector implements Renderable
{
    /**
     * @var App $app
     */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function collect()
    {
        return 'Versions';
    }

    public function getName()
    {
        return 'slim';
    }

    public function getWidgets()
    {
        $slim_version = App::VERSION;
        $php_version = PHP_VERSION;
        return [
            'mode' => [
                'icon' => 'info',
                'tooltip' => "Slim {$slim_version} | PHP {$php_version}",
                'map' => 'slim',
                'default' => '',
            ]
        ];
    }
}
