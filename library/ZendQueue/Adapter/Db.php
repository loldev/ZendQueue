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

use Zend\Db as ZendDb;
use Zend\Db\Sql\Sql;
use Zend\Stdlib\Message;
use ZendQueue\Exception;
use ZendQueue\Queue;
use ZendQueue\Adapter\Capabilities\CountMessagesCapableInterface;
use ZendQueue\Adapter\Capabilities\DeleteMessageCapableInterface;
use ZendQueue\Adapter\Capabilities\ListQueuesCapableInterface;
use ZendQueue\Adapter\Capabilities\ScheduleMessageCapableInterface;
use ZendQueue\Parameter\SendParameters;
use ZendQueue\Parameter\ReceiveParameters;
use ZendQueue\Adapter\AbstractAdapter;

/**
 * Class for using connecting to a Zend_DB-based queuing system
 *
 */
class Db extends AbstractAdapter implements
                                    CountMessagesCapableInterface,
                                    DeleteMessageCapableInterface,
                                    ListQueuesCapableInterface
{
    /**
     * User-provided options
     *
     * @var array
     */
    protected $_options = array(
        'queueTableName'     => 'queue',
        'messageTableName'   => 'message'
    );
    /**
     * @var ZendDb\TableGateway\TableGateway
     */
    protected $queueTable = null;

    /**
     * @var ZendDb\TableGateway\TableGateway
     */
    protected $messageTable = null;

    /**
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $adapter= null;

    /**
     * Constructor
     *
     * @param  array|Traversable $options
     */
    public function __construct($options)
    {
        parent::__construct($options);

        $this->connect();
    }

    /**
     * Get the TableGateway implementation of the queue table
     *
     * @return ZendDb\TableGateway\TableGateway
     */
    public function getQueueTable()
    {
        return $this->queueTable;
    }

    /**
     * Get the TableGateway implementation of the message table
     *
     * @return ZendDb\TableGateway\TableGateway
     */
    public function getMessageTable()
    {
        return $this->messageTable;
    }

    /**
     * Connect (or refresh connection) to the db adapter
     *
     * Throws an exception if the adapter cannot connect to DB.
     *
     * @return bool
     * @throws Exception\ConnectionException
     */
    public function connect()
    {
        try {
            $this->adapter = new ZendDb\Adapter\Adapter($this->_options['driverOptions']);
            $this->queueTable = new ZendDb\TableGateway\TableGateway($this->_options['queueTableName'], $this->adapter);
            $this->messageTable = new ZendDb\TableGateway\TableGateway($this->_options['messageTableName'], $this->adapter);
        } catch (ZendDb\Exception\ExceptionInterface $e) {
            throw new Exception\ConnectionException('Error connecting to database: ' . $e->getMessage(), $e->getCode(), $e);
        }

        return true;
    }


    /********************************************************************
     * Queue management functions
     *********************************************************************/

    public function getAvailableSendParams()
    {
        return array(
            SendParameters::SCHEDULE,
            SendParameters::INTERVAL,
            SendParameters::TIMEOUT
        );
    }

    public function getAvailableReceiveParams()
    {
        return array(
          ReceiveParameters::MSG_CLASS,
          ReceiveParameters::TIMEOUT,
        );
    }


    /**
     * Does a queue already exist?
     *
     * Throws an exception if the adapter cannot determine if a queue exists.
     * use isSupported('isExists') to determine if an adapter can test for
     * queue existance.
     *
     * @param  string $name
     * @return boolean
     * @throws Exception\ExceptionInterface
     */
    public function isExists($name)
    {
        $id = 0;

        try {
            $id = $this->getQueueId($name);
        } catch (\Exception $e) {
            return false;
        }

        return ($id > 0);
    }

    /**
     * Create a new queue
     *
     * @param  string  $name    queue name
     * @return boolean
     * @throws Exception\RuntimeException - database error
     */
    public function create($name)
    {
        if ($this->isExists($name)) {
            return false;
        }

        try {
            $result = $this->queueTable->insert(array('queue_name' => $name));
        } catch(\Exception $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if ($result) {
            return true;
        }

        return false;
    }

    /**
     * Delete a queue and all of it's messages
     *
     * Returns false if the queue is not found, true if the queue exists
     *
     * @param  string  $name queue name
     * @return boolean
     * @throws Exception\RuntimeException - database error
     */
    public function delete($name)
    {
        $id = $this->getQueueId($name); // get primary key

        // if the queue does not exist then it must already be deleted.
        $list = $this->queueTable->select(array('queue_id' => $id));
        if (count($list) === 0) {
            return false;
        }

        try {
            $remove = $this->queueTable->delete(array('queue_id' => $id));
        } catch (\Exception $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (array_key_exists($name, $this->_queues)) {
            unset($this->_queues[$name]);
        }

        return true;
    }

    /**
     * Get an array of all available queues
     *
     * Not all adapters support getQueues(), use isSupported('getQueues')
     * to determine if the adapter supports this feature.
     *
     * @return array
     * @throws Exception\ExceptionInterface - database error
     */
    public function getQueues()
    {
        $result = $this->queueTable->select();
        foreach($result as $one) {
            $this->_queues[$one['queue_name']] = (int)$one['queue_id'];
        }

        $list = array_keys($this->_queues);

        return $list;
    }

    /**
     * Return the approximate number of messages in the queue
     *
     * @param  Queue $queue
     * @return integer
     * @throws Exception\ExceptionInterface
     */
    public function countMessages(Queue $queue)
    {
        $info  = $this->messageTable->select(array('queue_id' => $this->getQueueId($queue->getName())));

        // return count results
        return $info->count();
    }

    /********************************************************************
    * Messsage management functions
     *********************************************************************/

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

        $msg = array(
            'queue_id' => $this->getQueueId($queue->getName()),
            'created'  => time(),
            'class'    => get_class($message),
            'content'  => (string) $message->getContent(),
            'metadata' => serialize($message->getMetadata()),
            'md5'      => md5($message->toString())
        );

        if ($params) {
            if ($params->getSchedule()) {
                $msg['schedule'] = $params->getSchedule();
            }

            if ($params->getInterval()) {
                $msg['interval'] = $params->getInterval();
            }
        }

        try {
            $id = $this->messageTable->insert($msg);
        } catch (\Exception $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        $this->_embedMessageInfo($queue, $message, $id, $params);

        return true;
    }

    /**
     * Get messages in the queue
     *
     * @param  Queue $queue
     * @param  integer|null $maxMessages Maximum number of messages to return
     * @param  ReceiveParameters $params
     * @return Message\MessageIterator
     * @throws Exception\RuntimeException - database error
     */
    public function receive(Queue $queue, $maxMessages = null, ReceiveParameters $params = null)
    {
        if ($maxMessages === null) {
            $maxMessages = 1;
        }

        $timeout   = $params ? $params->getTimeout() : null;
        $class     = $params ? $params->getMessageClass() : null;
        $msgs      = array();
        $name      = $this->messageTable->table;
        $microtime = (int) microtime(true); // cache microtime
        $db        = $this->messageTable->getAdapter();
        $connection = $db->getDriver()->getConnection();


        // start transaction handling
        try {
            if ($maxMessages > 0 ) { // ZF-7666 LIMIT 0 clause not included.
                $connection->beginTransaction();

                $sql = new Sql($this->adapter);
                $select = $sql->select();
                $select->from($name);

                $where = array(
                    'queue_id'                             => $this->getQueueId($queue->getName()),
                    '(schedule IS NULL or schedule < ?)'   => $microtime,
                    $timeout ? '(handle IS NULL OR timeout+' . $timeout . ' < ' . $microtime.')' : 'handle IS NULL',
                );

                if ($class) {
                    $where['class'] = (string) $class;
                }

                $select->where($where);
                $select->limit($maxMessages);

                $statement = $sql->prepareStatementForSqlObject($select);
                $result = $statement->execute();

                foreach($result as $message) {

                    $update = $sql->update($name);
                    $update->where(array('message_id' => $message['message_id']));
                    $update->where($where);
                    $message['handle'] = md5(uniqid(rand(), true));
                    $message['timeout'] = $microtime;
                    $update->set(array('handle' => $message['handle'], 'timeout' => $microtime));
                    $stmt = $sql->prepareStatementForSqlObject($update);
                    $rst = $stmt->execute();
                    if ($rst->count() > 0) {
                        $message['metadata'] = isset($message['metadata']) ? unserialize($message['metadata']) : array();
                        $message['metadata'][$queue->getOptions()->getMessageMetadatumKey()] = $this->_buildMessageInfo(
                            (int) $message['message_id'],
                            $queue,
                            array(
                                'timeout'     => $message['timeout'],
                                'schedule'    => $message['schedule'],
                                'interval'    => $message['interval'],
                            )
                        );
                        unset($message['id'], $message['timeout'], $message['schedule'], $message['interval']);


                        $msgs[] = $message;
                    }

                }
                $connection->commit();
            }
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }


        $classname = $queue->getOptions()->getMessageSetClass();
        return new $classname($msgs, $queue);
    }


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
    public function deleteMessage(Queue $queue, Message $message)
    {
        $info = $this->_extractMessageInfo($queue, $message);

        if (isset($info['messageId'])) {
            $db    = $this->messageTable->delete(array('message_id' => $info['messageId'], 'queue_id' => $this->getQueueId($queue->getName())));
            if ($db) {
                return true;
            }
        }
        return false;
    }



    /********************************************************************
     * Functions that are not part of the \ZendQueue\Adapter\AdapterAbstract
     *********************************************************************/
    /**
     * Get the queue ID
     *
     * Returns the queue's row identifier.
     *
     * @param  string       $name
     * @return integer|null
     * @throws Exception\QueueNotFoundException
     */
    protected function getQueueId($name)
    {
        if (array_key_exists($name, $this->_queues)) {
            return $this->_queues[$name];
        }

        $result = $this->queueTable->select(array('queue_name' => $name));
        foreach($result as $one) {
            $this->_queues[$name] = (int)$one['queue_id'];
        }

        if (!array_key_exists($name, $this->_queues)) {
            throw new Exception\QueueNotFoundException('Queue does not exist: ' . $name);
        }

        return $this->_queues[$name];
    }



}
