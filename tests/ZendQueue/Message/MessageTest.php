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
 * ZendQueue\Message is just a placeholder for Zend\Stdlib\Message
 * so we don't need ro repeat all tests.
 *
 * Just tests for seralization and check if it's the default message class
 *
 * @category   Zend
 * @package    Zend_Queue
 * @subpackage UnitTests
 * @group      Zend_Queue
 */
class MessageTest extends \PHPUnit_Framework_TestCase
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
     * @var Message
     */
    protected $message;


    protected function setUp()
    {
        $this->name = 'queueTest';

        $this->options = new QueueOptions();

        $this->adapter = new ArrayAdapter($this->options->getAdapterOptions());

        $this->queue = new Queue($this->name, $this->adapter);

        $this->message = new Message();

    }

    protected function tearDown()
    {
    }


    public function testSerialization()
    {
        $message = serialize($this->message);
        $woken = unserialize($message);
        $this->assertEquals($this->message->getContent(), $woken->getContent());
        $this->assertEquals($this->message->getMetadata(), $woken->getMetadata());

    }

   public function testDefaultMessageClass()
   {
       $this->queue->send('testMessage');
       $messages = $this->queue->receive();

       $this->assertInstanceOf('\ZendQueue\Message\Message', $messages->current());
   }


}
