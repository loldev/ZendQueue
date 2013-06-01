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

use Zend\Stdlib\Message;
use ZendQueue\Queue;
use ZendQueue\Exception;

/**
 * Class testing.  No supported functions.  Also used to disable a queue.
 *
 */
class Null extends AbstractAdapter
{

    /**
     * Ensure connection
     *
     * @return bool
     */
    public function connect()
    {
        return true;
    }

    /**
     * Does a queue already exist?
     *
     * @param  string $name Queue name
     * @return boolean
    */
    public function isExists($name)
    {
        return false;
    }

    /**
     * Create a new queue
     *
     * Visibility timeout is how long a message is left in the queue
     * "invisible" to other readers.  If the message is acknowleged (deleted)
     * before the timeout, then the message is deleted.  However, if the
     * timeout expires then the message will be made available to other queue
     * readers.
     *
     * @param  string  $name Queue name
     * @param  integer $timeout Default visibility timeout
     * @return boolean
    */
    public function create($name)
    {
        throw new Exception\UnsupportedMethodCallException(__FUNCTION__ . '() is not supported by ' . get_called_class());
    }

    /**
     * Delete a queue and all of its messages
     *
     * Return false if the queue is not found, true if the queue exists.
     *
     * @param  string $name Queue name
     * @return boolean
    */
    public function delete($name)
    {
        throw new Exception\UnsupportedMethodCallException(__FUNCTION__ . '() is not supported by ' . get_called_class());
    }


    /********************************************************************
     * Messsage management functions
    *********************************************************************/

    /**
     * Send a message to the queue
     *
     * @param  Queue $queue
     * @param  Message $message Message to send to the active queue
     * @param  SendParameters $params
     * @return bool
     * @throws Exception\QueueNotFoundException
     * @throws Exception\RuntimeException
    */
    public function send(Queue $queue, Message $message, SendParameters $params = null)
    {
        throw new Exception\UnsupportedMethodCallException(__FUNCTION__ . '() is not supported by ' . get_called_class());
    }

    /**
     * Get messages from the queue
     *
     * @param  Queue $queue
     * @param  integer|null $maxMessages Maximum number of messages to return
     * @param  ReceiveParameters $params
     * @return MessageIterator
    */
    public function receive(Queue $queue, $maxMessages = null, ReceiveParameters $params = null)
    {
        throw new Exception\UnsupportedMethodCallException(__FUNCTION__ . '() is not supported by ' . get_called_class());
    }


}
