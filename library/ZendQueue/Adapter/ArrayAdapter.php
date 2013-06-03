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
use ZendQueue\Message;
use ZendQueue\Queue;
use ZendQueue\Parameter\SendParameters;
use ZendQueue\Adapter\Capabilities\DeleteMessageCapableInterface;
use ZendQueue\Adapter\Capabilities\ListQueuesCapableInterface;
use ZendQueue\Adapter\Capabilities\CountMessagesCapableInterface;

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
     * Does a queue already exist?
     *
     * @param string $name
     * @return boolean
     */
    public function isExists($name)
    {
        return array_key_exists($name, $this->_data);
    }

    /**
     * Create a new queue
     *
     * @param string $name queue name
     * @return boolean
     */
    public function create($name, $timeout=null)
    {
        if ($this->isExists($name)) {
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
    public function delete($name)
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
     * Not all adapters support getQueues(), use isSupported('getQueues')
     * to determine if the adapter supports this feature.
     *
     * @return array
     */
    public function getQueues()
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
    public function count(Queue $queue)
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
     * @return array
     */
    public function getAvailableSendParams()
    {
        return array();
    }
    
    /**
     * Send a message to the queue
     *
     * @param Queue $queue
     * @param string $message
     * @param SendParameters $params
     * @return Message
     * @throws Exception\QueueNotFoundException
     */
    public function send(Queue $queue, Message $message, SendParameters $params = null)
    {
        if (!$this->isExists($queue->getName())) {
            throw new Exception\QueueNotFoundException('Queue does not exist:' . $queue->getName());
        }

        $this->_cleanMessageInfo($queue, $message);
        
        $msg = array(
            'created'  => time(),
            'class'    => get_class($queue),
            'content'  => (string) $message->getContent(),
            'metadata' => $message->getMetadata()
        );

        $messageId = microtime(true);
        $this->_data[$queue->getName()][$messageId] = $data;

        $options = array(
            'queue' => $queue,
            'data'  => $data,
        );
        
        $this->_embedMessageInfo($queue, $message, $messageId, $params);
        
        $classname = $queue->getMessageClass();
        return new $classname($options);
    }

    /**
     * Get messages in the queue
     *
     * @param Queue $queue
     * @param integer $maxMessages Maximum number of messages to return
     * @param ReceiveParameters $params
     * @return Message\MessageIterator
     */
    public function receive(Queue $queue, $maxMessages = null, ReceiveParameters $params = null)
    {
        if ($maxMessages === null) {
            $maxMessages = 1;
        }

        $data = array();
        if ($maxMessages > 0) {
            $start_time = microtime(true);

            $count = 0;
            $temp = &$this->_data[$queue->getName()];
            foreach ($temp as $messageId => &$msg) {

                if (($msg['handle'] === null)) {
                    $msg['handle']  = md5(uniqid(rand(), true));
                    $msg['timeout'] = microtime(true);
                    $data[] = $msg;
                    ++$count;
                }
                
                $message['metadata'][$queue->getOptions()->getMessageMetadatumKey()] = $this->_buildMessageInfo(
            		$messageId,
            		$queue
                );

                if ($count >= $maxMessages) {
                	break;
                }
            }
        }

        $options = array(
            'queue'        => $queue,
            'data'         => $data,
        );
        
        $classname = $queue->getOptions()->getMessageSetClass();
        return new $classname($options);
    }

    /**
     * Delete a message from the queue
     *
     * Returns true if the message is deleted, false if the deletion is unsuccessful.
     *
     * @param Queue $queue
     * @param  Message $message
     * @return boolean
     * @throws Exception\ExceptionInterface
     */
    public function deleteMessage(Queue $queue, Message $message)
    {
        if (!$this->isExists($queue->getName())) {
        	throw new Exception\QueueNotFoundException('Queue does not exist:' . $queue->getName());
        }
        
        // load the queue
        $queue = &$this->_data[$queue->getName()];

        if ( $queue[$messageId] === null ){
            return false;
        }
        
        unset($queue[$messageId]);
        
        return true;
    }

    /********************************************************************
    * Functions that are not part of the \ZendQueue\Adapter\AdapterAbstract
     *********************************************************************/

    //FIXME
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
