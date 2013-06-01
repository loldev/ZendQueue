<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueue\Adapter\Capabilities;

use Zend\Stdlib\Message;
use ZendQueue\Adapter\AdapterInterface;
use ZendQueue\Queue;

interface DeleteMessageCapableInterface extends AdapterInterface
{
     /**
     * Delete a message from the queue
     *
     * Return true if the message is deleted, false if the deletion is
     * unsuccessful.
     *
     * @param  Queue $queue
     * @param  Message $message
     * @return boolean
     */
    public function deleteMessage(Queue $queue, Message $message);
}