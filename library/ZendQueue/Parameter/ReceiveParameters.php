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

    const CLASS_FILTER = 'classFilter';
    const VISIBILITY_TIMEOUT  = 'visibilityTimeout';
    const PEEK_MODE = 'peekMode';

    /**
     * Set the class filter
     *
     * Filter (receive) only message of the given class name.
     *
     * @param string $classname
     * @return \ZendQueue\Parameter\ReceiveParameters
     */
    public function setClassFilter($classname = null)
    {
        if (($classname !== null) && !is_string($classname)) {
            throw new Exception\InvalidArgumentException('$classname must be a string or null');
        }

        //FIXME: temporary workaround to avoid absolute FQCN
        $classname = ltrim($classname, '\\');

        $this->set(self::CLASS_FILTER, $classname);
        return $this;
    }

    /**
     * Get the class filter
     *
     * @see setVisibilityTimeout()
     *
     * @return string|null
     */
    public function getClassFilter()
    {
        return $this->get(self::CLASS_FILTER, null);
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
    public function setVisibilityTimeout($timeout = null)
    {
        if (($timeout !== null) && !is_integer($timeout)) {
            throw new Exception\InvalidArgumentException('$timeout must be an integer or null');
        }

        $this->set(self::VISIBILITY_TIMEOUT, $timeout);
        return $this;
    }

    /**
     * Get the visibility timeout
     *
     * @see setVisibilityTimeout()
     *
     * @return int|null
     */
    public function getVisibilityTimeout()
    {
        return $this->get(self::VISIBILITY_TIMEOUT, null);
    }

    /**
     * Set peek mode
     *
     * If true, peek at the messages from the specified queue without removing them.
     *
     * @param bool $peek
     * @throws Exception\InvalidArgumentException
     * @return \ZendQueue\Parameter\ReceiveParameters
     */
    public function setPeekMode($peek = true)
    {
        if (!is_bool($peek)) {
            throw new Exception\InvalidArgumentException('$peek must be a boolean');
        }

        $this->set(self::PEEK_MODE, $peek);
        return $this;
    }

    /**
     * Get peek mode
     *
     * @see setPeekMode()
     *
     * @param bool $peek
     * @throws Exception\InvalidArgumentException
     * @return \ZendQueue\Parameter\ReceiveParameters
     */
    public function getPeekMode()
    {
        return $this->get(self::PEEK_MODE, null);
    }


}