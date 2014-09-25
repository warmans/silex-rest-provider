<?php
namespace SilexProvider\Rest\Provider;

use Silex\Provider\ServiceControllerServiceProvider;
use SilexProvider\Rest\RestService;

class RestServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RestServiceProvider
     */
    private $object;

    public function setUp()
    {
        $this->app = new \Silex\Application();
        $this->object = new RestServiceProvider();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRegisterWithoutControllerService()
    {
        $this->object->register($this->app);
    }

    public function testRegisterWithControllerServiceCreatesRestService()
    {
        $this->app->register(new ServiceControllerServiceProvider());
        $this->object->register($this->app);

        $this->assertTrue($this->app['rest'] instanceof RestService);
    }
}
 