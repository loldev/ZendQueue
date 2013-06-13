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
use ZendQueue\Queue;

use Zend\ServiceManager\ServiceManager;
use ZendQueue\Adapter\ArrayAdapter;
use ZendQueue\QueueOptions;

/**
 * @category   Zend
 * @package    Zend_Queue
 * @subpackage UnitTests
 * @group      Zend_Queue
 */
class QueueAbstractServiceFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $sm;

    public function setUp()
    {
        $adapter = new ArrayAdapter();
        $adapter->setOptions(array('dummyOption' => 'dummyValue'));

        $this->sm = new ServiceManager();
        $this->sm->setService('Config', array(
          'queues' => array(

            'queueA' => array(
                'name'     => 'A',
                'adapter'  => array( //Adapter as config
                    'adapter' => 'ArrayAdapter',
                    'options' => array('dummyOption' => 'dummyValue'),
                 ),
                'options' => array('messageClass' => 'Zend\Stdlib\Message'),
            ),


            'queueB' => array(
                'name'     => 'B',
                'adapter'  => $adapter, //Adapter as instance
                'options' => array('messageClass' => 'Zend\Stdlib\Message'),
            ),

        )));
        $this->sm->addAbstractFactory('ZendQueue\Service\QueueAbstractServiceFactory');
    }

    public function tearDown()
    {

    }

    public function testCanLookupQueueByName()
    {
        $this->assertTrue($this->sm->has('queueA'));
        $this->assertTrue($this->sm->has('queueB'));
    }

    public function testCanRetrieveQueueByName()
    {
        $qA = $this->sm->get('queueA');
        $this->assertInstanceOf('ZendQueue\Queue', $qA);

        $qB = $this->sm->get('queueB');
        $this->assertInstanceOf('ZendQueue\Queue', $qB);

        $this->assertNotSame($qA, $qB);
    }

    public function testConfiguration()
    {
        $qA = $this->sm->get('queueA');
        $this->assertInstanceOf('ZendQueue\Adapter\ArrayAdapter', $qA->getAdapter());

        if ($qA->getOptions() instanceof ZendQueue\Adapter\AdapterInterface) {
            $options = $qA->getAdapter()->getOptions();
            $this->assertEquals('dummyValue', $options['dummyOption']);
        }

        $this->assertInstanceOf('ZendQueue\QueueOptions', $qA->getOptions());
        if ($qA->getOptions() instanceof QueueOptions) {
            $this->assertEquals('Zend\Stdlib\Message', $qA->getOptions()->getMessageClass());
        }


        $qB = $this->sm->get('queueB');
        $this->assertInstanceOf('ZendQueue\Adapter\ArrayAdapter', $qB->getAdapter());

        if ($qB->getOptions() instanceof ZendQueue\Adapter\AdapterInterface) {
            $options = $qB->getAdapter()->getOptions();
            $this->assertEquals('dummyValue', $options['dummyOption']);
        }

        $this->assertInstanceOf('ZendQueue\QueueOptions', $qB->getOptions());
        if ($qB->getOptions() instanceof QueueOptions) {
            $this->assertEquals('Zend\Stdlib\Message', $qB->getOptions()->getMessageClass());
        }

    }

    public function testInvalidServiceNameWillBeIgnored()
    {
        $this->assertFalse($this->sm->has('invalid'));
    }
}