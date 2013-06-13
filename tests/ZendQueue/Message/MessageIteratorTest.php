<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueueTest\Message;

use ZendQueue\Queue;
use ZendQueue\Message\Message;
use ZendQueue\Message\MessageIterator;
use ZendQueue\QueueOptions;
use ZendQueue\Adapter\ArrayAdapter;

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
class MessageIteratorTest extends \PHPUnit_Framework_TestCase
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


    /**
     * @var MessageIterator
     */
    protected $messages;

    protected function setUp()
    {
        $this->name = 'queueTest';

        $this->options = new QueueOptions();

        $this->adapter = new ArrayAdapter();

        $this->queue = new Queue($this->name, $this->adapter, $this->options);

        // construct messages
        $this->message_count = 5;
        $data  = array();
        $this->metadata = array(
            'one' => 1,
            'two' => 2,
        );
        for ($i = 0; $i < $this->message_count; $i++) {
            $data[] = array(
                 'class'     => '\ZendQueue\Message\Message',
                 'metadata'  => $this->metadata,
                 'content'   => 'Hello world',
            );
        }

        $classname = $this->queue->getOptions()->getMessageSetClass();
        $this->messages = new $classname($data, $this->queue);
    }


    public function test_setup()
    {
        $classname = $this->queue->getOptions()->getMessageSetClass();
        $this->assertTrue($this->queue instanceof Queue);
        $this->assertTrue($this->options instanceof QueueOptions);
        $this->assertInstanceOf($classname, $this->messages);

        foreach ($this->messages as $i => $message) {
            $this->assertTrue($message instanceof Message);
            $this->assertEquals('Hello world', $message->getContent());
            $this->assertEquals($this->metadata, $message->getMetadata());
        }
    }

    public function testConstruct()
    {
        $message = new Message();
        $message->setContent('A message');

        $stdMessage = new \Zend\Stdlib\Message();
        $stdMessage->setContent('A stdlib message');

        //Test array of Message, without queue in constructor
        $messages = new MessageIterator(array($message, $stdMessage));

        $this->assertEquals($message, $messages->current());
        $messages->next();
        $this->assertEquals($stdMessage, $messages->current());
        $this->assertNull($messages->getQueue());
        $this->assertNull($messages->getQueueClass());

        //Test array of array, without queue in constructor
        $messages = new MessageIterator(array(array('content' => 'a message')));
        $this->assertEquals('a message', $messages->current()->getContent());
        $this->assertInstanceOf($this->queue->getOptions()->getMessageClass(), $messages->current());

        //Test with queue in constructor
        $messages = new MessageIterator(array($message, $stdMessage), $this->queue);
        $this->assertTrue($this->queue === $messages->getQueue());
        $this->assertEquals(get_class($this->queue), $this->messages->getQueueClass());

    }

    public function test_count()
    {
        $this->assertEquals($this->message_count, count($this->messages));
    }

    public function test_magic()
    {
        $this->assertTrue(is_array($this->messages->__sleep()));

        $messages = serialize($this->messages);
        $woken = unserialize($messages);
        $this->assertEquals($this->messages->current()->getContent(), $woken->current()->getContent());
        $this->assertNull($woken->getQueue());
    }

    public function test_get_setQueue()
    {
        $queue = $this->messages->getQueue();
        $this->assertTrue($queue instanceof Queue);

        $this->assertTrue($this->messages->setQueue($queue) instanceof MessageIterator);

        $this->assertTrue($this->messages->getQueue() === $queue);

    }

    public function test_getQueueClass()
    {
        $this->assertEquals(get_class($this->queue), $this->messages->getQueueClass());
    }

    public function test_iterator()
    {
        foreach ($this->messages as $i => $message) {
            $this->assertEquals('Hello world', $message->getContent());
            $this->assertEquals($this->metadata, $message->getMetadata());
        }
    }

}
