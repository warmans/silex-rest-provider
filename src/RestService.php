<?php
namespace SilexProvider\Rest;

use Silex\Application;

class RestService
{
    /**
     * @var \Silex\Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Import complete api config from an array of root resources.
     *
     * @param array $restConfig
     */
    public function importApi(array $restConfig)
    {
        foreach ($restConfig as $rootResource) {
            $this->createResource($rootResource);
        }
    }

    /**
     * Create a resource and all its sub-resources.
     *
     * @param array $resourceConfig
     * @param array $parentUris
     * @throws \RuntimeException
     */
    public function createResource(array $resourceConfig, array $parentUris=array())
    {
        if (empty($resourceConfig['uri']) || empty($resourceConfig['ctl'])) {
            throw new \RuntimeException('Invalid resource config encountered. Config must contain uri and ctl keys');
        }

        if ($resourceConfig['ctl'] instanceof \Closure) {
            //registers controller factory inline
            $controllerName = $this->registerController($resourceConfig['uri'], $resourceConfig['ctl'], $parentUris);
        } elseif (is_string($resourceConfig['ctl'])) {
            $controllerName = $resourceConfig['ctl'];
        } else {
            throw new \RuntimeException('Ctl must be a factory (Closure) or existing service (string name)');
        }

        //setup routes
        $this->app->get(
            $this->createRouteUri($resourceConfig['uri'], $parentUris, true),
            sprintf('%s:get', $controllerName)
        );

        $this->app->get(
            $this->createRouteUri($resourceConfig['uri'], $parentUris, false),
            sprintf('%s:cget', $controllerName)
        );

        $this->app->post(
            $this->createRouteUri($resourceConfig['uri'], $parentUris, false),
            sprintf('%s:post', $controllerName)
        );

        $this->app->put(
            $this->createRouteUri($resourceConfig['uri'], $parentUris, true),
            sprintf('%s:put', $controllerName)
        );

        $this->app->patch(
            $this->createRouteUri($resourceConfig['uri'], $parentUris, true),
            sprintf('%s:patch', $controllerName)
        );

        $this->app->delete(
            $this->createRouteUri($resourceConfig['uri'], $parentUris, true),
            sprintf('%s:delete', $controllerName)
        );

        //handle sub resources
        if (!empty($resourceConfig['sub'])) {

            if (!is_array($resourceConfig['sub'])) {
                throw new \RuntimeException('sub config must contain array of sub resources');
            }

            //append current uri as parent
            $parentUris[] = $resourceConfig['uri'];

            foreach($resourceConfig['sub'] as $subResource) {
                $this->createResource($subResource, $parentUris);
            }
        }
    }

    /**
     * Register a controller if one is passed as part of the resource config.
     *
     * @param $uri
     * @param callable $factory
     * @param array $parentUris
     * @return string
     */
    public function registerController($uri, \Closure $factory, array $parentUris=array())
    {
        //attempt to normalize name to dot-separated string
        $fullStack = array_map(
            function ($val) {
                return preg_replace('#[\\/]+#', '.', trim($val, '\\/'));
            },
            array_merge($parentUris, [$uri])
        );

        $name = 'rest.ctl.'.implode('.', $fullStack);
        $this->app[$name] = $factory;

        return $name;
    }

    /**
     * Given the parents and current uri create a routeable uri. If getUri is true also include an id
     * for the right-most resource. For as many IDs as exist in the full path append another ID to the route param.
     *
     * @param $uri
     * @param array $parentUris
     * @param bool $getUri
     * @return string
     */
    public function createRouteUri($uri, $parentUris=array(), $getUri=false)
    {
        $id = 'id';
        $fullUri = [];
        foreach ($parentUris as $part) {
            $fullUri[] = trim($part, '\\/');
            $fullUri[] = '{'.$id.'}';
            $id .= 'd';
        }

        return '/'.(count($fullUri) ? implode('/', $fullUri).'/' : '').trim($uri, '\\/').($getUri ? '/{'.$id.'}' : '');
    }
}
