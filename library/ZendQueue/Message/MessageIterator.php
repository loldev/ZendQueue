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
    protected $_data = array();

     /**
     * Connected is true if we have a reference to a live
     * Queue object.
     * This is false after the Message has been deserialized.
     *
     * @var boolean
     */
    protected $_connected = true;

    /**
     * Queue instance
     *
     * @var Queue
     */
    protected $_queue = null;

    /**
     * Name of the class of the Adapter object.
     *
     * @var string
     */
    protected $_queueClass = null;

    /**
     * Name of the queue
     *
     * @var string
     */
    protected $_queueName = null;

    /**
     * Default Message class name, only used if not specified in data
     *
     * @var string
     */
    protected $_messageClass = '\ZendQueue\Message\Message';


     /**
     * MessageIterator pointer.
     *
     * @var integer
     */
    protected $_pointer = 0;

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
            $this->_queue          = $queue;
            $this->_queueClass     = get_class($queue);
            $this->_queueName      = $queue->getName();
            $this->_messageClass   = $queue->getOptions()->getMessageClass();
            $this->_connected      = true;
        } else {
            $this->_connected = false;
        }

        $this->_data = $data;
    }

    protected function _lazyMessageFactory($index)
    {
        if (!($this->_data[$index] instanceof Message)) {
            $data = $this->_data[$index];
            $msgClass = isset($data['class']) ? $data['class'] : $this->_messageClass;

            /* @var $message \Zend\Stdlib\Message */
            $message = new $msgClass;

            if (isset($data['content'])) {
                $message->setContent($data['content']);
            }

            if (isset($data['metadata'])) {
                $message->setMetadata($data['metadata']);
            }

            $this->_data[$index] = $message;
        }
        return $this->_data[$index];
    }

    /**
     * Store queue and data in serialized object
     *
     * @return array
     */
    public function __sleep()
    {
        return array('_data', '_queueClass', '_queueName', '_messageClass', '_pointer');
    }

    /**
     * Setup to do on wakeup.
     * A de-serialized Message should not be assumed to have access to a live
     * queue connection, so set _connected = false.
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->_connected = false;
    }


    /**
     * Returns the queue object, or null if this is disconnected message set
     *
     * @return Queue|null
     */
    public function getQueue()
    {
        return $this->_queue;
    }

//     /**
//      * Set the queue object, to re-establish a live connection
//      * to the queue for a Message that has been de-serialized.
//      *
//      * @param  AdapterInterface $queue
//      * @return boolean
//      * @throws Exception\ExceptionInterface
//      */
//     public function setQueue(Queue $queue)
//     {
//         $this->_queue     = $queue;
//         $this->_connected = false;

//         // @todo This works only if we have iterated through
//         // the result set once to instantiate the rows.
//         foreach ($this->_data as $i => $message) {
//             $this->_connected = $this->_connected || $message->setQueue($queue);
//         }

//         return $this->_connected;
//     }

    /**
     * Query the class name of the Queue object for which this
     * Message was created.
     *
     * @return string
     */
    public function getQueueClass()
    {
        return $this->_queueClass;
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
        $this->_pointer = 0;
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
            : $this->_lazyMessageFactory($this->_pointer)); // return the messages object
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
        return $this->_pointer;
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
        ++$this->_pointer;
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
        return $this->_pointer < count($this->_data);
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
        return count($this->_data);
    }
}
