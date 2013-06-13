<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueueTest\Adapter;

use ZendQueue\Adapter;

/**
 * @category   Zend
 * @package    Zend_Queue
 * @subpackage UnitTests
 * @group      Zend_Queue
 */
class AdapterFactoryTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        Adapter\AdapterFactory::resetAdapterPluginManager();
    }

    public function tearDown()
    {
       Adapter\AdapterFactory::resetAdapterPluginManager();
    }

    public function testDefaultAdapterPluginManager()
    {
        $adapters = Adapter\AdapterFactory::getAdapterPluginManager();
        $this->assertInstanceOf('ZendQueue\Adapter\AdapterPluginManager', $adapters);
    }

    public function testChangeAdapterPluginManager()
    {
        $adapters = new Adapter\AdapterPluginManager();
        Adapter\AdapterFactory::setAdapterPluginManager($adapters);
        $this->assertSame($adapters, Adapter\AdapterFactory::getAdapterPluginManager());
    }

    public function testAdapterFactory()
    {
        $adapter1 = Adapter\AdapterFactory::factory(array(
                'adapter' => 'ArrayAdapter',
                'options' => array('dummyOption' => 'dummyValue'),
        ));
        $this->assertInstanceOf('ZendQueue\Adapter\ArrayAdapter', $adapter1);

        $adapter2 = Adapter\AdapterFactory::factory(array(
                'adapter' => 'ArrayAdapter',
                'options' => array('dummyOption' => 'dummyValue'),
        ));
        $this->assertInstanceOf('ZendQueue\Adapter\ArrayAdapter', $adapter2);

        $this->assertNotSame($adapter1, $adapter2);
    }


    public function testFactoryAdapterAsString()
    {
        $adapter = Adapter\AdapterFactory::factory(array(
            'adapter' => 'Null',
        ));
        $this->assertInstanceOf('ZendQueue\Adapter\Null', $adapter);
    }

    public function testFactoryWithAdapterAsStringAndOptions()
    {
        $adapter = Adapter\AdapterFactory::factory(array(
            'adapter' => 'Null',
            'options' => array(
                'dummy' => 'test'
            ),
        ));

        $this->assertInstanceOf('ZendQueue\Adapter\Null', $adapter);
        $options = $adapter->getOptions();
        $this->assertSame('test', $options['dummy']);
    }

    public function testFactoryWithAdapterAsInstanceAndOptions()
    {
        $adapter = Adapter\AdapterFactory::factory(array(
            'adapter' => new Adapter\Null(),
            'options' => array(
                'dummy' => 'test'
            ),
        ));

        $this->assertInstanceOf('ZendQueue\Adapter\Null', $adapter);
        $options = $adapter->getOptions();
        $this->assertSame('test', $options['dummy']);
    }
}