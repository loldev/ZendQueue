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
use ZendQueue\SpecificationInterface as Queue;
use ZendQueue\Adapter\Capabilities\DeleteMessageCapableInterface;
use ZendQueue\Adapter\Mongo\AbstractMongo;

class MongoCollection extends AbstractMongo implements DeleteMessageCapableInterface
{
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

        $info = $this->getMessageInfo($queue, $message);
        if (isset($info['messageId'])) {
            $collection = $this->mongoDb->selectCollection($queue->getName());
            $result = $collection->remove(array('_id' => $info['messageId'], self::KEY_HANDLE => $info['handle']));
            if (isset($result['ok']) && $result['ok']) {
                $this->cleanMessageInfo($queue, $message);
                return true;
            }
        }

        return false;
    }
}