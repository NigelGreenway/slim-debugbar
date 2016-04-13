<?php namespace DebugBar;

use Slim\Http\Response;

class SlimHttpDriver extends PhpHttpDriver
{
    /**
     * @var Response
     */
    protected $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function setHeaders(array $headers)
    {
        foreach ($headers as $key => $val) {
            $this->response->withHeader($key, $val);
        }
    }
}