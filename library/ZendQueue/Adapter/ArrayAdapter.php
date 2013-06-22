<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueue\Adapter;

use ZendQueue\Exception;
use ZendQueue\Queue;
use ZendQueue\Parameter\SendParameters;
use ZendQueue\Adapter\Capabilities\DeleteMessageCapableInterface;
use ZendQueue\Adapter\Capabilities\ListQueuesCapableInterface;
use ZendQueue\Adapter\Capabilities\CountMessagesCapableInterface;
use Zend\Stdlib\MessageInterface;
use ZendQueue\Parameter\ReceiveParameters;
use ZendQueue\QueueOptions;
use Zend\Math\Rand;

/**
 * Class for using a standard PHP array as a queue
 *
 * @category   Zend
 * @package    Zend_Queue
 * @subpackage Adapter
 */
class ArrayAdapter extends AbstractAdapter implements DeleteMessageCapableInterface, ListQueuesCapableInterface, CountMessagesCapableInterface
{
    /**
     * @var array
     */
    protected $_data = array();


    /**
     * List avaliable params for receiveMessages()
     *
     * @return array
     */
    public function getAvailableReceiveParams()
    {
        return array(
            ReceiveParameters::VISIBILITY_TIMEOUT,
            ReceiveParameters::CLASS_FILTER,
        );
    }


    /********************************************************************
    * Queue management functions
     *********************************************************************/

    /**
     * Ensure connection
     *
     * Dummy method - ArrayAdapter needs no connection
     *
     * @return boolean
     */
    public function connect()
    {
    	return true;
    }

    /**
     * Returns the ID of the queue
     *
     * @param string $name Queue name
     * @return string
     */
    public function getQueueId($name)
    {
        if ($this->queueExists($name)) {
            return $name;
        }
        //else
        return null;
    }

    /**
     * Check if a queue exists
     *
     * @param string $name
     * @return boolean
     */
    public function queueExists($name)
    {
        return array_key_exists($name, $this->_data);
    }

    /**
     * Create a new queue
     *
     * @param string $name queue name
     * @return boolean
     */
    public function createQueue($name)
    {
        if ($this->queueExists($name)) {
            return false;
        }

        $this->_data[$name] = array();

        return true;
    }

    /**
     * Delete a queue and all of it's messages
     *
     * Returns false if the queue is not found, true if the queue exists
     *
     * @param string $name queue name
     * @return boolean
     */
    public function deleteQueue($name)
    {
        $found = isset($this->_data[$name]);

        if ($found) {
            unset($this->_data[$name]);
        }

        return $found;
    }

    /**
     * Get an array of all available queues
     *
     * @return array
     */
    public function listQueues()
    {
        return array_keys($this->_data);
    }

    /**
     * Return the approximate number of messages in the queue
     *
     * @param Queue $queue
     * @return integer
     * @throws Exception\QueueNotFoundException
     */
    public function countMessages(Queue $queue)
    {
        if (!isset($this->_data[$queue->getName()])) {
            throw new Exception\QueueNotFoundException('Queue does not exist');
        }

        return count($this->_data[$queue->getName()]);
    }

    /********************************************************************
    * Messsage management functions
     *********************************************************************/

    /**
     * Send a message to the queue
     *
     * @param Queue $queue
     * @param MessageInterface $message
     * @param SendParameters $params
     * @return MessageInterface
     * @throws Exception\QueueNotFoundException
     */
    public function sendMessage(Queue $queue, MessageInterface $message, SendParameters $params = null)
    {
        if (!$this->queueExists($queue->getName())) {
            throw new Exception\QueueNotFoundException('Queue does not exist: ' . $queue->getName());
        }

        $this->_cleanMessageInfo($queue, $message);

        $msg = array(
            'created'  => time(),
            'class'    => get_class($message),
            'content'  => (string) $message->getContent(),
            'metadata' => $message->getMetadata(),
            'handle'   => null,
        );

        $_queue = &$this->_data[$queue->getName()];

        $messageId = md5(Rand::getString(10).count($_queue));

        $_queue[$messageId] = $msg;

        $options = array(
            'queue' => $queue,
            'data'  => $msg,
        );

        $this->_embedMessageInfo($queue, $message, $messageId, $params);

        return $message;
    }

    /**
     * Get messages in the queue
     *
     * @param Queue $queue
     * @param integer $maxMessages Maximum number of messages to return
     * @param ReceiveParameters $params
     * @return Message\MessageIterator
     */
    public function receiveMessages(Queue $queue, $maxMessages = null, ReceiveParameters $params = null)
    {
        if ($maxMessages === null) {
            $maxMessages = 1;
        }

        $timeout = $params ? $params->getVisibilityTimeout() : null;
        $filter  = $params ? $params->getClassFilter() : null;

        $data = array();
        if ($maxMessages > 0) {
            $start_time = microtime(true);

            $count = 0;
            $temp = &$this->_data[$queue->getName()];
            foreach ($temp as $messageId => &$msg) {

                if (null !== $filter && $msg['class'] != $filter) {
                    continue;
                }

                if ($msg['handle'] === null || ( $msg['timeout'] !== null && $msg['timeout'] < microtime(true))) {

                    $msg['handle']  = md5(uniqid(rand(), true));
                    $msg['timeout'] = $params ? microtime(true) + $timeout : null;
                    $msg['metadata'][$queue->getOptions()->getMessageMetadatumKey()] = $this->_buildMessageInfo(
                        $msg['handle'],
                		$messageId,
                		$queue
                    );

                    $data[] = $msg;
                    ++$count;
                }

                if ($count >= $maxMessages) {
                	break;
                }
            }
        }

        $classname = $queue->getOptions()->getMessageSetClass();
        return new $classname($data, $queue);
    }

    /**
     * Delete a message from the queue
     *
     * Return true if the message is deleted, false if the deletion is
     * unsuccessful.
     *
     * @param  Queue $queue
     * @param  MessageInterface $message
     * @return boolean
     * @throws Exception\QueueNotFoundException
     */
    public function deleteMessage(Queue $queue, MessageInterface $message)
    {
        if (!$this->queueExists($queue->getName())) {
        	throw new Exception\QueueNotFoundException('Queue does not exist:' . $queue->getName());
        }

        $info = $this->getMessageInfo($queue, $message);
        $messageId = $info['messageId'];

        // load the queue
        $queue = &$this->_data[$queue->getName()];

        if (!array_key_exists($messageId, $queue)) {
            return false;
        }

        unset($queue[$messageId]);

        return true;
    }

    /********************************************************************
    * Functions that are not part of the \ZendQueue\Adapter\AdapterAbstract
     *********************************************************************/

    /**
     * serialize
     */
    public function __sleep()
    {
    	return array('_data');
    }

    /*
     * These functions are debug helpers.
    */

    /**
     * returns underlying _data array
     * $queue->getAdapter()->getData();
     *
     * @return ArrayAdapter
     */
    public function getData()
    {
    	return $this->_data;
    }

    /**
     * sets the underlying _data array
     * $queue->getAdapter()->setData($data);
     *
     * @param $data array
     * @return ArrayAdapter
     */
    public function setData($data)
    {
    	$this->_data = $data;
    	return $this;
    }
}
