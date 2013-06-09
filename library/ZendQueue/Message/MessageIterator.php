<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueue\Message;

use Countable;
use Iterator;
use ZendQueue\Queue;
use Zend\Stdlib\Message as StdMessage;

/**
 *
 */
class MessageIterator implements Countable, Iterator
{
    /**
     * The data for the queue message
     *
     * @var array
     */
    protected $data = array();

    /**
     * Queue instance
     *
     * @var Queue
     */
    protected $queue = null;

    /**
     * Name of the class of the queue object.
     *
     * @var string
     */
    protected $queueClass = null;

    /**
     * Name of the queue
     *
     * @var string
     */
    protected $queueName = null;

    /**
     * Default Message class name, only used if not specified in data
     *
     * @var string
     */
    protected $messageClass = '\ZendQueue\Message\Message';


     /**
     * MessageIterator pointer.
     *
     * @var integer
     */
    protected $pointer = 0;

    /**
     * Constructor
     *
     * $data items must be
     *  - Message object
     * OR
     *  - array(
     *    'class'     => string (message class, optional)
     *    'metadata'  => array
     *    'content'   => string
     *  )
     *
     *
     * @param array $data
     * @param Queue $queue
     */
    public function __construct(array $data = array(), Queue $queue = null)
    {
        if ($queue) {
            $this->setQueue($queue);
        }

        $this->data = $data;
    }

    protected function _lazyMessageFactory($index)
    {
        if (!($this->data[$index] instanceof StdMessage)) {
            $data = $this->data[$index];
            $msgClass = isset($data['class']) ? $data['class'] : $this->messageClass;

            /* @var $message \Zend\Stdlib\Message */
            $message = new $msgClass;

            if (isset($data['content'])) {
                $message->setContent($data['content']);
            }

            if (isset($data['metadata'])) {
                $message->setMetadata($data['metadata']);
            }

            $this->data[$index] = $message;
        }
        return $this->data[$index];
    }

    /**
     * Store queue and data in serialized object excluding the queue instance
     *
     * @return array
     */
    public function __sleep()
    {
        return array('data', 'queueClass', 'queueName', 'messageClass', 'pointer');
    }

    /**
     * Returns the queue object, or null if this is disconnected message set
     *
     * @return Queue|null
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Set the queue object, to re-establish a live connection
     * when iterator has been de-serialized.
     *
     * @param  Queue $queue
     * @return MessageIterator
     */
    public function setQueue(Queue $queue)
    {
        $this->queue          = $queue;
        $this->queueClass     = get_class($queue);
        $this->queueName      = $queue->getName();
        $this->messageClass   = $queue->getOptions()->getMessageClass();

        return $this;
    }

    /**
     * Query the class name of the Queue object for which this
     * MessageIterator was created.
     *
     * @return string
     */
    public function getQueueClass()
    {
        return $this->queueClass;
    }

    /*
     * MessageIterator implementation
     */

    /**
     * Rewind the MessageIterator to the first element.
     * Similar to the reset() function for arrays in PHP.
     * Required by interface MessageIterator.
     *
     * @return void
     */
    public function rewind()
    {
        $this->pointer = 0;
    }

    /**
     * Return the current element.
     * Similar to the current() function for arrays in PHP
     * Required by interface MessageIterator.
     *
     * @return \Zend\Stdlib\Message current element from the collection
     */
    public function current()
    {
        return (($this->valid() === false)
            ? null
            : $this->_lazyMessageFactory($this->pointer)); // return the messages object
    }

    /**
     * Return the identifying key of the current element.
     * Similar to the key() function for arrays in PHP.
     * Required by interface MessageIterator.
     *
     * @return integer
     */
    public function key()
    {
        return $this->pointer;
    }

    /**
     * Move forward to next element.
     * Similar to the next() function for arrays in PHP.
     * Required by interface MessageIterator.
     *
     * @return void
     */
    public function next()
    {
        ++$this->pointer;
    }

    /**
     * Check if there is a current element after calls to rewind() or next().
     * Used to check if we've iterated to the end of the collection.
     * Required by interface MessageIterator.
     *
     * @return bool False if there's nothing more to iterate over
     */
    public function valid()
    {
        return $this->pointer < count($this->data);
    }

    /*
     * Countable Implementation
     */

    /**
     * Returns the number of elements in the collection.
     *
     * Implements Countable::count()
     *
     * @return integer
     */
    public function count()
    {
        return count($this->data);
    }
}
