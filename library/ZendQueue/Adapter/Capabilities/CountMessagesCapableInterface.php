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

use ZendQueue\Adapter\AdapterInterface;
use ZendQueue\Queue;

interface CountMessagesCapableInterface extends AdapterInterface
{
    /**
     * Returns the approximate number of messages in the queue
     *
     * @return integer|null
     */
    public function countMessages(Queue $queue);
}