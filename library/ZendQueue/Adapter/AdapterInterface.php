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
     * Constructor
     *
     * @param  array|Traversable $options
     * @return void
     */
    public function __construct($options);

    /**
     * Ensure connection
     *
     * @return bool
     */
    public function connect();

    /**
     * Does a queue already exist?
     *
     * @param  string $name Queue name
     * @return boolean
     */
    public function isExists($name);

    /**
     * Create a new queue
     *
     * @param  string  $name Queue name
     * @return boolean
     */
    public function create($name);

    /**
     * Delete a queue and all of its messages
     *
     * Return false if the queue is not found, true if the queue exists.
     *
     * @param  string $name Queue name
     * @return boolean
     */
    public function delete($name);


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
    public function send(Queue $queue, Message $message, SendParameters $params = null);

    /**
     * Get messages from the queue
     *
     * @param  Queue $queue
     * @param  integer|null $maxMessages Maximum number of messages to return
     * @param  ReceiveParameters $params
     * @return MessageIterator
     */
    public function receive(Queue $queue, $maxMessages = null, ReceiveParameters $params = null);


    /********************************************************************
     * Supporting functions
     *********************************************************************/

    public function getAvailableReceiveParams();

    public function getAvailableSendParams();

    /**
     * Returns the configuration options in this adapter.
     *
     * @return array
     */
    public function getOptions();

}
