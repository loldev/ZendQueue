<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueue;

use Countable;
use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\Message;
use ZendQueue\Exception;
use ZendQueue\Adapter\AdapterInterface;
use ZendQueue\Adapter\Capabilities\ListQueuesCapableInterface;
use ZendQueue\Adapter\Capabilities\CountMessagesCapableInterface;
use ZendQueue\Adapter\Capabilities\DeleteMessageCapableInterface;
use ZendQueue\Adapter\Capabilities\ScheduleMessageCapableInterface;
use ZendQueue\Parameter\SendParameters;
use ZendQueue\Parameter\ReceiveParameters;
use ZendQueue\Adapter\Capabilities\FilterMessageCapableInterface;
use ZendQueue\Adapter\Capabilities\VisibilityTimeoutCapableInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\Event;
use ZendQueue\Adapter\Capabilities\AwaitCapableInterface;
use ZendQueue\Adapter\Null;

/**
 *
 */
class Queue implements Countable
{

    /**
     * Queue name
     *
     * @var string
     */
    protected $name;

    /**
     * @var AdapterInterface
     */
    protected $adapter = null;

    /**
     * User-provided configuration
     *
     * @var QueueOptions
     */
    protected $options;

    /**
     * Constructor
     *
     * Can be called as
     * $queue = new Queue('default', $config);
     * - or -
     * $queue = new Queue('default', 'ArrayAdapter', $config);
     * - or -
     * $queue = new Queue('default', null, $config); // Queue->createQueue();
     *
     * @param  string $name
     * @param  string|AdapterInterface|array|Traversable|null $adapter
     * @param  Traversable|array $options
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($name, $adapter, $options = array())
    {
        if (empty($name)) {
            throw new Exception\InvalidArgumentException('No valid param $name passed to constructor: cannot be empty');
        }
        $this->name = $name;

        $this->setOptions($options);

        $this->setAdapter($adapter);
    }


    /**
     * Set options
     *
     * @param  array|\Traversable|QueueOptions $options
     * @return Queue
     */
    public function setOptions($options)
    {
        if (!$options instanceof QueueOptions) {
            $options = new QueueOptions($options);
        }

        $this->options = $options;
        return $this;
    }

    /**
     * Get options
     *
     * @return QueueOptions
     */
    public function getOptions()
    {
        if ($this->options === null) {
            $this->options = new QueueOptions();
        }
        return $this->options;
    }

    /**
     * Get Queue name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Set the adapter for this queue
     *
     * @param  string|AdapterInterface $adapter
     * @return Queue Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function setAdapter($adapter)
    {
        if (is_string($adapter)) {
            $adapterName = $this->getOptions()->getAdapterNamespace() . '\\' . $adapter;

            /*
             * Create an instance of the adapter class.
             * Pass the configuration to the adapter class constructor.
             */
            $options = $this->getOptions();
            $adapter = new $adapterName(array('options' => $options->getAdapterOptions(), 'driverOptions' => $options->getDriverOptions() ));
        }

        if (($type = gettype($adapter)) != 'object') {
            throw new Exception\InvalidArgumentException('$adapter must be a string or an object implementing \ZendQueue\Adapter\AdapterInterface. '.$type.' given.');
        }

        if (!$adapter instanceof AdapterInterface) {
            throw new Exception\InvalidArgumentException("Adapter class ".get_class($adapter)." does not implement \ZendQueue\Adapter\AdapterInterface");
        }

        $this->adapter = $adapter;

        if (! ($this->adapter instanceof Null)) {
            $this->adapter->create($this->getName());
        }

        return $this;
    }

    /**
     * Get the adapter for this queue
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }


    /**
     * Ensure that this queue exist
     *
     * @return bool
     * @throws Exception\InvalidArgumentException
     */
    public function ensureQueue()
    {
        $name = $this->getName();
        if($this->getAdapter()->isExists($name)) {
            return true;
        }

        return $this->getAdapter()->create($name);
    }

    /**
     * Delete the queue this object is working on.
     *
     * This queue is disabled, regardless of the outcome of the deletion
     * of the queue, because the programmers intent is to disable this queue.
     *
     * @return boolean
     */
    public function deleteQueue()
    {
        $name = $this->getName();
        $adapter = $this->getAdapter();

        $deleted = false;

        if($adapter->isExists($name)) {
            $deleted = $adapter->delete($name);
        }

        /**
         * @see Adapter\Null
         */
        $this->setAdapter('Null');

        return $deleted;
    }

    /**
     * Delete a message from the queue
     *
     * Returns true if the message is deleted, false if the deletion is
     * unsuccessful.
     *
     *
     * @param  Message $message
     * @return boolean
     * @throws Exception\UnsupportedMethodCallException
     */
    public function deleteMessage(Message $message)
    {
        if (!$this->canDeleteMessage()) {
            throw new Exception\UnsupportedMethodCallException(__FUNCTION__ . '() is not supported by ' . get_class($this->getAdapter()));
        }

        return $this->getAdapter()->deleteMessage($this, $message);
    }

    /**
     * Send a message to the queue
     *
     * @param  mixed $message message
     * @param  SendParamters $params
     * @return bool
     * @throws Exception\ExceptionInterface
     */
    public function send($message, SendParameters $params = null)
    {
        if (!($message instanceof Message)) {
            $data = $message;
            $messageClass = $this->getOptions()->getMessageClass();
            if (is_string($data)) {
                $message = new $messageClass;
                $message->setContent($data);
            } else if(is_array($data) && isset($data['content'])) {
                $message = new $messageClass;
                $message->setContent((string) $data['content']);
                if (isset($data['metadata'])) {
                    $message->setMetadata($data['metadata']);
                }
            } else {
                throw new Exception\InvalidArgumentException('Invalid $message type');
            }
        }

        return $this->getAdapter()->send($this, $message, $params);
    }


    /**
     * Schedule a message to the queue
     *
     * @param  mixed $message message
     * @param  int $schedule
     * @param  int $interval
     * @param  SendParamters $params
     * @return bool
     * @throws Exception\UnsupportedMethodCallException
     */
    public function schedule($message, $schedule = null, $interval = null, SendParameters $params = null)
    {
        if (!$this->isSendParamSupported(SendParameters::SCHEDULE)) {
            throw new Exception\UnsupportedMethodCallException(__FUNCTION__ . '() is not supported by ' . get_class($this->getAdapter()));
        }

        if ($interval !== null && !$this->isSendParamSupported(SendParameters::INTERVAL)) {
            if (!$this->isSendParamSupported(SendParameters::SCHEDULE)) {
                throw new Exception\UnsupportedMethodCallException('\'interval\' param is not supported by ' . get_class($this->getAdapter()));
            }

        }

        if ($params === null) {
            $params = new SendParameters();
        }

        $params->setScheduling($schedule, $interval);

        return $this->send($message, $params);
    }

    /**
     * Returns the approximate number of messages in the queue
     *
     * Returns null if the adapter doesn't support message count.
     *
     * @return integer|null
     */
    public function count()
    {
        if ($this->canCountMessages()) {
            return $this->getAdapter()->countMessages($this);
        }
        return null;
    }

    /**
     * Return the first element in the queue
     *
     * @param  integer $maxMessages
     * @param  ReceiveParameters $params
     * @return Message\MessageIterator
     * @throws Exception\InvalidArgumentException
     */
    public function receive($maxMessages = 1, ReceiveParameters $params = null)
    {
        if (($maxMessages !== null) && (!is_integer($maxMessages) || $maxMessages < 1)) {
            throw new Exception\InvalidArgumentException('$maxMessages must be an integer greater than 0 or null');
        }

        return $this->getAdapter()->receive($this, $maxMessages, $params);
    }


    /**
     * Await messages
     *
     * @param  ReceiveParameters $params
     * @param  mixed $eventManagerOrClosure
     * @return Message
     * @throws Exception\InvalidArgumentException
     */
    public function await(ReceiveParameters $params = null, $eventManagerOrClosure = null)
    {

        if ($eventManagerOrClosure instanceof EventManagerInterface) {
            $closure = function(Message $message) use($eventManagerOrClosure) {
                $event = new Event();
                $event->setParam('message', $message);
                return !$eventManagerOrClosure->trigger($event)->stopped();
            };
        } elseif ($eventManagerOrClosure instanceof \Closure) {
            $closure = $eventManagerOrClosure;
        } elseif ($eventManagerOrClosure === null) {
            $closure = null;
        } else {
            throw new Exception\InvalidArgumentException('Invalid $eventManagerOrClosure type: must be EventManagerInterface, Closure or null.');
        }

        //the adpater support await?
        if ($this->getAdapter() instanceof AwaitCapableInterface) {
            return $this->getAdapter()->await($this, $closure, $params);
        }

        //can emulate await?
        if ($this->getOptions()->getEnableAwaitEmulation()) {

            $sleepSeconds = $this->getOptions()->getPollingInterval();

            do {
                $await = true;
                $message = null;
                $messages = $this->receive(1, $params);

                if ($messages->count()) {
                    $message = $messages->current();
                    $await = $closure($message);
                } else {
                    sleep($sleepSeconds);
                }

            } while($await);

            return $message;
        }

        throw new Exception\UnsupportedMethodCallException(__FUNCTION__ . '() is not supported by ' . get_class($this->getAdapter()) . ' and await emulation is not enabled.');
    }


    /**
     * Get an array of all available queues
     *
     * @return array
     * @throws Exception\UnsupportedMethodCallException
     */
    public function getQueues()
    {
        if (!$this->canListQueues()) {
            throw new Exception\UnsupportedMethodCallException(__FUNCTION__ . '() is not supported by ' . get_class($this->getAdapter()));
        }

        return $this->getAdapter()->getQueues();
    }


    /********************************************************************
     * Available Parameters
    *********************************************************************/

    public function isSendParamSupported($name)
    {
        return in_array(strtolower($name), $this->getAdapter()->getAvailableSendParams());
    }

    public function isReceiveParamSupported($name)
    {
        return in_array(strtolower($name), $this->getAdapter()->getAvailableReceiveParams());
    }

    /********************************************************************
     * Capabilities
    *********************************************************************/

    /**
     * Can queue wait for messages?
     *
     * Return true if the adapter is await-capable or enableAwaitEmulation is active.
     *
     * @return bool
     */
    public function canAwait()
    {
        return ($this->getAdapter() instanceof AwaitCapableInterface) || $this->getOptions()->getEnableAwaitEmulation();
    }

    /**
     * Is queue using await emulations?
     *
     * Return true if the adapter isn't await-capable and enableAwaitEmulation is active.
     *
     * @return bool
     */
    public function isAwaitEmulation()
    {
        return !($this->getAdapter() instanceof AwaitCapableInterface) && $this->getOptions()->getEnableAwaitEmulation();
    }

    /**
     * Can queue delete message?
     *
     * Return true if the adapter is capable to delete messages.
     *
     * @return bool
     */
    public function canDeleteMessage()
    {
        return $this->getAdapter() instanceof DeleteMessageCapableInterface;
    }

    /**
     * Can count in queue messages?
     *
     * Return true if the adapter can count messages.
     *
     * @return bool
     */
    public function canCountMessages()
    {
        return $this->getAdapter() instanceof CountMessagesCapableInterface;
    }


    /**
     * Can list all available queues?
     *
     * Return true if the adapter can list all queues available for the current adapter.
     *
     * @return bool
     */
    public function canListQueues()
    {
        return $this->getAdapter() instanceof ListQueuesCapableInterface;
    }


    /**
     * returns a listing of Queue details.
     * useful for debugging
     *
     * @return array
     */
    public function debugInfo()
    {
        $info = array();
        $info['self']                     = get_called_class();
        $info['adapter']                  = get_class($this->getAdapter());
        $info['currentQueue']             = $this->getName();
        $info['messageClass']             = $this->getOptions()->getMessageClass();
        $info['messageSetClass']          = $this->getOptions()->getMessageSetClass();

        return $info;
    }



}