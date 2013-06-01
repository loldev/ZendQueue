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
use ZendQueue\Parameter\ReceiveParameters;

interface AwaitCapableInterface extends AdapterInterface
{
    /**
     * Await for messages in the queue and receive them
     *
     * @param  Queue $queue
     * @param  Closure $callback
     * @param  ReceiveParameters $params
     * @return Message
     * @throws Exception\RuntimeException - database error
     */
     public function await(Queue $queue, \Closure $callback = null, ReceiveParameters $params = null);
}