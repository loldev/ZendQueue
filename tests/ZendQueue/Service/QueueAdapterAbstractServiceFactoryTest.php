<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueue\Service;

use ZendQueue\Adapter;

use Zend\ServiceManager\ServiceManager;

/**
 * @category   Zend
 * @package    Zend_Queue
 * @subpackage UnitTests
 * @group      Zend_Queue
 */
class QueueAdapterAbstractServiceFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $sm;

    public function setUp()
    {
        Adapter\AdapterFactory::resetAdapterPluginManager();

        $this->sm = new ServiceManager();
        $this->sm->setService('Config', array('queue_adapters' => array(
            'ArrayAdapter' => array(
                'adapter' => 'ArrayAdapter',
                'options' => array('dummyOption' => 'dummyValue'),
            ),
            'Foo' => array(
                'adapter' => 'ArrayAdapter',
                'options' => array('dummyOption' => 'dummyValue'),
            ),
        )));
        $this->sm->addAbstractFactory('ZendQueue\Service\QueueAdapterAbstractServiceFactory');
    }

    public function tearDown()
    {
         Adapter\AdapterFactory::resetAdapterPluginManager();
    }

    public function testCanLookupAdapterByName()
    {
        $this->assertTrue($this->sm->has('ArrayAdapter'));
        $this->assertTrue($this->sm->has('Foo'));
    }

    public function testCanRetrieveAdapterByName()
    {
        $adapterA = $this->sm->get('ArrayAdapter');
        $this->assertInstanceOf('ZendQueue\Adapter\ArrayAdapter', $adapterA);

        $adapterB = $this->sm->get('Foo');
        $this->assertInstanceOf('ZendQueue\Adapter\ArrayAdapter', $adapterB);

        $this->assertNotSame($adapterA, $adapterB);
    }

    public function testInvalidCacheServiceNameWillBeIgnored()
    {
        $this->assertFalse($this->sm->has('invalid'));
    }
}