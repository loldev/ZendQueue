<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueueTest;

use Zend\Config\Config;
use ZendQueue\Adapter;
use ZendQueue\Message\Message;
use ZendQueue\Queue;
use ZendQueue\QueueOptions;
use ZendQueue\Adapter\ArrayAdapter;
use ZendQueue\Message\MessageIterator;
use ZendQueue\Parameter\SendParameters;

/*
 * The adapter test class provides a universal test class for all of the
 * abstract methods.
 *
 * All methods marked not supported are explictly checked for for throwing
 * an exception.
 */

/**
 * @category   Zend
 * @package    Zend_Queue
 * @subpackage UnitTests
 * @group      Zend_Queue
 */
class QueueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var ArrayAdapter
     */
    protected $adapter;

    /**
     * @var QueueOptions
     */
    protected $options;

    /**
     * @var Queue
     */
    protected $queue;


    protected function setUp()
    {
        $this->name = 'queueTest';

        $this->options = new QueueOptions();

        $this->adapter = new ArrayAdapter();

        $this->queue = new Queue($this->name, $this->adapter, $this->options);

        $this->queue->ensureQueue();
    }

    protected function tearDown()
    {

    }

    public function testSetGetConfig()
    {
        $this->assertTrue($this->options instanceof QueueOptions);
        $this->assertEquals($this->options, $this->queue->getOptions());

        $options = new QueueOptions();

        $this->assertTrue($this->queue->setOptions($options) instanceof Queue);
        $this->assertEquals($options, $this->queue->getOptions());
    }

    public function testGetAdapter()
    {
        $this->assertTrue($this->queue->getAdapter() instanceof ArrayAdapter);
    }

    public function testGetName()
    {
        $this->assertEquals($this->name, $this->queue->getName());
    }

    public function testEnsureQueue()
    {
        $this->assertTrue($this->queue->ensureQueue());
        $this->assertTrue($this->adapter->isQueueExist($this->name));
    }

    public function testSampleBehavior()
    {
        // ------------------------------------ send()
        // parameter verification
        try {
            $this->queue->send(array());
            $this->fail('send() $mesage must be a string or an instance of \Zend\Stdlib\MessageInterface');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        $message = 'Hello world';
        $this->assertInstanceOf('\Zend\Stdlib\MessageInterface', $this->queue->send($message));

        $message = new Message();
        $message->setContent('Hello world again');
        $this->assertEquals($message, $this->queue->send($message));

        // ------------------------------------ count()
        if ($this->queue->canCountMessages()) {
            $this->assertEquals($this->queue->count(), 2);
        }

        // ------------------------------------ receive()
        // parameter verification
        try {
            $this->queue->receive(array());
            $this->fail('receive() $maxMessages must be a integer or null');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        // parameter verification
        try {
            $this->queue->receive(0);
            $this->fail('receive() $maxMessages must be a integer or null');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        $messages = $this->queue->receive();
        $this->assertTrue($messages instanceof MessageIterator);

        // ------------------------------------ deleteMessage()
        if($this->queue->canDeleteMessage()) {
            foreach ($messages as $i => $message) {
                $this->assertTrue($message instanceof Message);
                $this->assertTrue($this->queue->deleteMessage($message));
            }
        }
    }

    public function testSchedule()
    {
        if (!$this->queue->isSendParamSupported(SendParameters::SCHEDULE)) {
            $this->markTestSkipped('schedule() not supported');
        }

        $this->assertTrue($this->queue->schedule('Hello World', 2, $interval));

        if ($this->queue->isSendParamSupported(SendParameters::INTERVAL)) {
            $this->assertTrue($this->queue->schedule('Hello World', 2, 2));
        }
    }

    /**
     * ArrayAdapter can't await
     * @todo add EventManager case
     */
    public function testAwait()
    {
        if (!$this->queue->canAwait()) {
            $this->markTestSkipped('await() not supported');
        }

        //Ensure we have one message at least
        $this->queue->send('test');

        $queueTest = $this;
        $this->queue->await(null, function() use($queueTest) {
        	$queueTest->assertTrue(true);
        	return false; //stop awaiting
        });
    }

    public function testGetQueues()
    {
        if (!$this->queue->canListQueues()) {
            $this->markTestSkipped("canListQueues() is not supported");
        }

        $queues = $this->queue->listQueues();

        $this->assertTrue(is_array($queues));

        $this->assertTrue(in_array($this->name, $queues));
    }
}
