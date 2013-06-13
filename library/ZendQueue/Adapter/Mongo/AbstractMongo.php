<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueue\Adapter\Mongo;

use ZendQueue\Adapter\AbstractAdapter;
use ZendQueue\Adapter\Capabilities\CountMessagesCapableInterface;
use ZendQueue\Adapter\Capabilities\AwaitCapableInterface;
use ZendQueue\Exception;
use ZendQueue\Queue;
use ZendQueue\Parameter\SendParameters;
use ZendQueue\Parameter\ReceiveParameters;
use Zend\Stdlib\Message;

abstract class AbstractMongo extends AbstractAdapter implements CountMessagesCapableInterface
{

    const KEY_HANDLED     = 'h';
    const KEY_CLASS       = 't';
    const KEY_CONTENT     = 'c';
    const KEY_METADATA    = 'm';

    /**
     * Ensure connection
     *
     * @return bool
     */
    public function connect()
    {
        $driverOptions = $this->getOptions()['driverOptions'];

        if (isset($driverOptions['dsn']) && is_string($driverOptions['dsn'])) {
            $dsn = $driverOptions['dsn'];
        } else {
            if (!isset($driverOptions['host'])) {
                throw new Exception\InvalidArgumentException(__FUNCTION__ . ' expects a "host" key to be present inside the driverOptions, if "dsn" key is not used');
            }

            if (!isset($driverOptions['dbname'])) {
                throw new Exception\InvalidArgumentException(__FUNCTION__ . ' expects a "dbname" key to be present inside the driverOptions, if "dsn" key is not used');
            }



            $credentials =     array_key_exists('username', $driverOptions) && !empty($driverOptions['username'])
            && array_key_exists('password', $driverOptions) && !empty($driverOptions['password']) ?
            $driverOptions['username'] . ':' . $driverOptions['password'] . '@'
                :
                '';

            $dsn = "mongodb://$credentials{$driverOptions['host']}/{$driverOptions['dbname']}";
        }

        $mongo = new \Mongo($dsn, $options);

        $dbName = explode('/', $dsn);
        $dbName = array_pop($dbName);

        $this->mongoDb = $mongo->{$dbName};

        return true;
    }

    /* (non-PHPdoc)
     * @see \ZendQueue\Adapter\AdapterInterface::create()
    */
    public function create($name)
    {
        $this->_queues[$name] = $this->mongoDb->createCollection($name);
        return true;
    }


    /**
     *
     * @param  string $name
     * @return boolean
     * @throws Exception\ExceptionInterface
     */
    public function isExists($name)
    {
        $collection = $this->mongoDb->selectCollection($name);
        $result = $collection->validate();
        return (isset($result['valid']) && $result['valid']) ? true : false;
    }

    /**
     * Delete a queue and all of its messages
     *
     * Return false if the queue is not found, true if the queue exists.
     *
     * @param  string $name Queue name
     * @return boolean
     */
    public function delete($name)
    {
        $result = $this->mongoDb->selectCollection($name)->drop();
        if(isset($result['ok']) && $result['ok']) {
            unset($this->_queues[$name]);
            return true;
        }

        return false;
    }


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
    public function send(Queue $queue, Message $message, SendParameters $params = null)
    {
        $this->_cleanMessageInfo($queue, $message);

        $collection = $this->mongoDb->selectCollection($queue->getName());

        $id = new \MongoId();
        $msg = array(
            '_id'              => $id,
            self::KEY_CLASS    => get_class($message),
            self::KEY_CONTENT  => (string) $message->getContent(),
            self::KEY_METADATA => $message->getMetadata(),
            self::KEY_HANDLED  => false,
        );

        try {
            $collection->insert($msg);
        } catch (\Exception $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        $this->_embedMessageInfo($queue, $message, $id, $params ? $params->toArray() : array());

        return true;
    }

    protected function _setupCursor(\MongoCollection $collection, ReceiveParameters $params = null,
        $criteria = array(self::KEY_HANDLED => false),
        array $fields = array('_id', self::KEY_HANDLED)
    )
    {
        if($params) {
            if ($params->getMessageClass()) {
                $criteria[self::KEY_CLASS] = $params->getMessageClass();
            }
        }

        return $collection->find($criteria, $fields);
    }

    protected function _receiveMessageAtomic(Queue $queue, \MongoCollection $collection, $id)
    {
        $msg = $collection->findAndModify(
            array('_id' => $id),
            array('$set' => array(self::KEY_HANDLED => true)),
            null,
            array(
                'sort'   => array('$natural' => 1),
                'new'    => false, //message returned does not include the modifications made on the update
            )
        );

        //if message has been handled already then ignore it
        if(empty($msg) || $msg[self::KEY_HANDLED]) { //already handled
            return null;
        }

        $msg[self::KEY_METADATA] = (array) $msg[self::KEY_METADATA];
        $msg[self::KEY_METADATA][$queue->getOptions()->getMessageMetadatumKey()] = $this->_buildMessageInfo($msg['_id'], $queue);

        return array(
            'class'    => $msg[self::KEY_CLASS],
            'content'  => $msg[self::KEY_CONTENT],
            'metadata' => $msg[self::KEY_METADATA]
        );
    }

    /**
     * Get messages from the queue
     *
     * @param  Queue $queue
     * @param  integer|null $maxMessages Maximum number of messages to return
     * @param  ReceiveParameters $params
     * @return MessageIterator
     */
    public function receive(Queue $queue, $maxMessages = null, ReceiveParameters $params = null)
    {
        if ($maxMessages === null) {
            $maxMessages = 1;
        }

        $collection = $this->mongoDb->selectCollection($queue->getName());

        $cursor = $this->_setupCursor($collection, $params);
        $cursor->limit((int) $maxMessages);

        $msgs = array();

        foreach ($cursor as $msg) {
            $msg = $this->_receiveMessageAtomic($queue, $collection, $msg['_id']);
            if($msg) {
                $msgs[] = $msg;
            }
        }

        $classname = $queue->getOptions()->getMessageSetClass();
        return new $classname($msgs, $queue);
    }

    /**
     * Returns the approximate number of messages in the queue
     *
     *
     * @return integer
     */
    public function countMessages(Queue $queue)
    {
        $collection = $this->mongoDb->selectCollection($queue->getName());
        return $collection->count(array(self::KEY_HANDLED => false));
    }

}
