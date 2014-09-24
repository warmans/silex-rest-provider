<?php
namespace SilexProvider\Rest\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\ServiceControllerResolver;
use SilexProvider\Rest\RestService;

class RestServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!($app['resolver'] instanceof ServiceControllerResolver)) {
            throw new \RuntimeException('ServiceControllerServiceProvider is required.');
        }

        $app['rest'] = $app->share(function($app){
            return new RestService($app);
        });
    }

    public function boot(Application $app)
    {
    }
}
