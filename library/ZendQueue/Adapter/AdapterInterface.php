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

use Zend\Stdlib\MessageInterface;
use ZendQueue\Message\MessageIterator;
use ZendQueue\Queue;
use ZendQueue\Parameter\SendParameters;
use ZendQueue\Parameter\ReceiveParameters;

/**
 * Interface for common queue operations
 */
interface AdapterInterface
{
    /**
     * Constructor.
     *
     * $options is an array of key/value pairs or an instance of Traversable
     * containing configuration options.
     *
     * @param  array|Traversable $options An array having configuration data
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options = array());


    /**
     * Set options
     *
     * @param array|Traversable $options
     * @return AdapterInterface Fluent interface
     */
    public function setOptions($options);

    /**
     * Get options
     *
     * @return array
    */
    public function getOptions();


    /**
     * List avaliable params for sendMessage()
     *
     * @return array
     */
    public function getAvailableSendParams();

    /**
     * List avaliable params for receiveMessages()
     *
     * @return array
     */
    public function getAvailableReceiveParams();


    /**
     * Ensure connection
     *
     * @return bool
     */
    public function connect();


    /********************************************************************
     * Queue management functions
    *********************************************************************/

    /**
     * Returns the ID of the queue
     *
     * @param string $name Queue name
     * @return mixed
     */
    public function getQueueId($name);

    /**
     * Check if a queue exists
     *
     * @param  string $name Queue name
     * @return boolean
     */
    public function queueExists($name);

    /**
     * Create a new queue
     *
     * @param  string  $name Queue name
     * @return boolean
     */
    public function createQueue($name);

    /**
     * Delete a queue and all of its messages
     *
     * Return false if the queue is not found, true if the queue exists.
     *
     * @param  string $name Queue name
     * @return boolean
     */
    public function deleteQueue($name);


    /********************************************************************
     * Message management functions
     *********************************************************************/

    /**
     * Send a message to the queue
     *
     * @param  Queue $queue
     * @param  MessageInterface $message Message to send to the active queue
     * @param  SendParameters $params
     * @return MessageInterface
     * @throws Exception\QueueNotFoundException
     * @throws Exception\RuntimeException
     */
    public function sendMessage(Queue $queue, MessageInterface $message, SendParameters $params = null);

    /**
     * Get messages from the queue
     *
     * @param  Queue $queue
     * @param  integer|null $maxMessages Maximum number of messages to return
     * @param  ReceiveParameters $params
     * @return MessageIterator
     * @throws Exception\QueueNotFoundException
     * @throws Exception\RuntimeException
     */
    public function receiveMessages(Queue $queue, $maxMessages = null, ReceiveParameters $params = null);

    /**
     * Get message info
     *
     * Only received messages have embedded infos.
     *
     * @param Queue $queue
     * @param MessageInterface $message
     * @return array
     */
    public function getMessageInfo(Queue $queue, MessageInterface $message);

}
