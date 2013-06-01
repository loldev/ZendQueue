<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueue\Parameter;

use ZendQueue\Exception;
use Zend\Stdlib\Parameters;
class ReceiveParameters extends Parameters
{

    const MSG_CLASS = 'class';
    const TIMEOUT  = 'timeout';


    public function setMessageClass($class)
    {
        $this->set(self::MSG_CLASS, $class);
        return $this;
    }

    public function getMessageClass()
    {
        return $this->get(self::MSG_CLASS, null);
    }

    /**
     * Set the visibility timeout
     *
     * Visibility timeout is how long a message is left in the queue
     * "invisible" to other readers.  If the message is acknowleged (deleted)
     * before the timeout, then the message is deleted.  However, if the
     * timeout expires then the message will be made available to other queue
     * readers.
     *
     * @param int $timeout
     * @throws Exception\InvalidArgumentException
     * @return \ZendQueue\Parameter\ReceiveParameters
     */
    public function setTimeout($timeout)
    {
        if (($timeout !== null) && !is_integer($timeout)) {
            throw new Exception\InvalidArgumentException('$timeout must be an integer or null');
        }

        $this->set(self::TIMEOUT, $timeout);
        return $this;
    }

    public function getTimeout()
    {
        return $this->get(self::TIMEOUT, null);
    }




}