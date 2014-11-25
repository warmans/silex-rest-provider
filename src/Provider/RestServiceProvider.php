<?php
namespace SilexProvider\Rest\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\ServiceControllerResolver;
use SilexProvider\Rest\RestService;

class RestServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        if (!($app['resolver'] instanceof ServiceControllerResolver)) {
            throw new \RuntimeException('ServiceControllerServiceProvider is required.');
        }

        $app['rest'] = function($app){
            return new RestService($app);
        };
    }

    public function boot(Application $app)
    {
    }
}
