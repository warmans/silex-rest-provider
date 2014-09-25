<?php
namespace SilexProvider\Rest;

use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__.'/fixture/SampleController.php';

class RestServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RestService
     */
    private $object;

    private $app;

    public function setUp()
    {
        $this->app = $this->createApplication();
        $this->object = new RestService($this->app);
    }

    private function createApplication()
    {
        $app = new Application(array('debug'=>true));
        $app->register(new ServiceControllerServiceProvider());
        $app->register(new Provider\RestServiceProvider($app));
        return $app;
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCreateResourceChecksUri()
    {
        $this->object->createResource(array('ctl'=>'foo'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCreateResourceChecksCtl()
    {
        $this->object->createResource(array('uri'=>'foo'));
    }

    public function testCreateResourceGeneratesRoutes()
    {
        $this->app['rest']->createResource([
            'uri' => '/user',
            'ctl' => function () { return new \SampleController(); }
        ]);

        $this->assertEquals('cget', $this->app->handle(Request::create('/user'))->getContent());
        $this->assertEquals('get-foo', $this->app->handle(Request::create('/user/foo'))->getContent());
        $this->assertEquals('post', $this->app->handle(Request::create('/user', 'POST'))->getContent());
        $this->assertEquals('put-foo', $this->app->handle(Request::create('/user/foo', 'PUT'))->getContent());
        $this->assertEquals('patch-foo', $this->app->handle(Request::create('/user/foo', 'PATCH'))->getContent());
    }

    public function testCreateSubResourceGeneratesRoutes()
    {
        $this->app['rest']->createResource([
            'uri' => '/api/user',
            'ctl' => function () { return new \SampleController(1); },
            'sub' => [[
                'uri' => '/event',
                'ctl' => function () { return new \SampleController(2); },
                'sub' => [[
                    'uri' => '/subscription',
                    'ctl' => function () { return new \SampleController(3); },
                ]]
            ]]
        ]);

        $this->assertEquals('3cget1-2', $this->app->handle(Request::create('/api/user/1/event/2/subscription'))->getContent());
        $this->assertEquals('3get-1-2-3', $this->app->handle(Request::create('/api/user/1/event/2/subscription/3'))->getContent());
        $this->assertEquals('3post1-2', $this->app->handle(Request::create('/api/user/1/event/2/subscription', 'POST'))->getContent());
        $this->assertEquals('3put-1-2-3', $this->app->handle(Request::create('/api/user/1/event/2/subscription/3', 'PUT'))->getContent());
        $this->assertEquals('3patch-1-2-3', $this->app->handle(Request::create('/api/user/1/event/2/subscription/3', 'PATCH'))->getContent());
    }

    public function testCreateResourceFromExistingControllerGeneratesRoutes()
    {
        $this->app['my.controller'] = function () {
            return new \SampleController();
        };

        $this->app['rest']->createResource([
            'uri' => '/user',
            'ctl' => 'my.controller'
        ]);

        $this->assertEquals('cget', $this->app->handle(Request::create('/user'))->getContent());
        $this->assertEquals('get-foo', $this->app->handle(Request::create('/user/foo'))->getContent());
        $this->assertEquals('post', $this->app->handle(Request::create('/user', 'POST'))->getContent());
        $this->assertEquals('put-foo', $this->app->handle(Request::create('/user/foo', 'PUT'))->getContent());
        $this->assertEquals('patch-foo', $this->app->handle(Request::create('/user/foo', 'PATCH'))->getContent());
    }

    public function testImportApiGeneratesRoutes()
    {
        $this->app['rest']->importApi([[
            'uri' => '/user',
            'ctl' => function () { return new \SampleController(); }
        ]]);

        $this->assertEquals('cget', $this->app->handle(Request::create('/user'))->getContent());
        $this->assertEquals('get-foo', $this->app->handle(Request::create('/user/foo'))->getContent());
        $this->assertEquals('post', $this->app->handle(Request::create('/user', 'POST'))->getContent());
        $this->assertEquals('put-foo', $this->app->handle(Request::create('/user/foo', 'PUT'))->getContent());
        $this->assertEquals('patch-foo', $this->app->handle(Request::create('/user/foo', 'PATCH'))->getContent());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidSubResource()
    {
        $this->app['rest']->createResource([
            'uri' => '/api/user',
            'ctl' => function () { return new \SampleController(1); },
            'sub' => [[
                'uri' => '/event',
                'ctl' => function () { return new \SampleController(2); },
                'sub' => 'invalid'
            ]]
        ]);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidController()
    {
        $this->app['rest']->createResource([
            'uri' => '/api/user',
            'ctl' => new \stdClass()
        ]);
    }

    public function testRegisterControllerInvokesPimple()
    {
        $factory = function(){ return 'ok'; };

        $this->object->registerController('/user', $factory);
        $this->assertEquals('ok', $this->app['rest.ctl.user']);
    }

    public function testRegisterControllerGeneratesUniqueName()
    {
        $this->assertEquals('rest.ctl.user', $this->object->registerController('/user', function(){}));
    }

    public function testRegisterControllerGeneratesUniqueNameWithParents()
    {
        $this->assertEquals(
            'rest.ctl.my.first.second.user',
            $this->object->registerController('/user', function(){}, ['/my/first/', '/second'])
        );
    }

    public function testCreateSimpleRouteUri()
    {
        $this->assertEquals('/user', $this->object->createRouteUri('/user'));
    }

    public function testCreateRouteUriWithParents()
    {
        $this->assertEquals(
            '/foo/{id}/bar/{idd}/user',
            $this->object->createRouteUri('/user', array('/foo', '/bar'))
        );
    }

    public function testCreateGetRouteUri()
    {
        $this->assertEquals(
            '/user/{id}',
            $this->object->createRouteUri('/user', array(), true)
        );
    }

    public function testCreateGetRouteUriWithParents()
    {
        $this->assertEquals(
            '/foo/{id}/bar/{idd}/user/{iddd}',
            $this->object->createRouteUri('/user', array('/foo', '/bar'), true)
        );
    }
}
