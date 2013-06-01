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

use Mongo;
use MongoDB;
use MongoCollection;
use MongoId;
use Zend\Stdlib\Message;
use ZendQueue\Exception;
use ZendQueue\Queue;
use ZendQueue\Adapter\Capabilities\AwaitCapableInterface;
use ZendQueue\Parameter\SendParameters;
use ZendQueue\Parameter\ReceiveParameters;
use ZendQueue\Adapter\Mongo\AbstractMongo;

class MongoCappedCollection extends AbstractMongo implements AwaitCapableInterface
{

    const SIZE = 'size';
    const MAX_MESSAGES = 'maxMessages';


    const DEFAULT_SIZE = 1000000;
    const DEFAULT_MAX_MESSAGES = 100;


    /**
     * User-provided options
     *
     * @var array
     */
    protected $_options = array(
        self::SIZE          => self::DEFAULT_SIZE,
        self::MAX_MESSAGES   => self::DEFAULT_MAX_MESSAGES,
    );


    /**
     * Create a new queue
     *
     * @param  string  $name Queue name
     * @return boolean
     */
    public function create($name)
    {
        $this->_queues[$name] = $this->mongoDb->createCollection($name, true, $this->_options[self::SIZE], $this->_options[self::MAX_MESSAGES]);

        for($i=0; $i < 100; $i++){
            $this->_queues[$name]->insert(array(self::KEY_HANDLED => true));
        }
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
        return (isset($result['capped']) && $collection->count() > 0) ? true : false;
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

        if($collection->count(array(self::KEY_HANDLED => true)) < 10) {
            return false;
        }

        $id = new MongoId();
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


    /**
     * Await for messages in the queue and receive them
     *
     * @param  Queue $queue
     * @param  Closure $callback
     * @param  ReceiveParameters $params
     * @return Message
     * @throws Exception\RuntimeException - database error
     */
    public function await(Queue $queue, \Closure $callback = null, ReceiveParameters $params = null)
    {
        $classname = $queue->getOptions()->getMessageSetClass();
        $collection = $this->mongoDb->selectCollection($queue->getName());

        do {

            //FIXME:
            $cursor = $collection->find()->sort(array('_id' => -1));
            $i = 0;
            foreach ($cursor as $lastValue) {
                if($i == 1)
                    break;
                $i++;
            }

            $cursor = $this->_setupCursor($collection, $params, array('_id' => array('$gt' => $lastValue['_id'])), array('_id', self::KEY_HANDLED));
            $cursor->tailable(true);
            $cursor->awaitData(true);

            do {
                if (!$cursor->hasNext()) {
                    // we've read all the results or cursor is dead
                    if ($cursor->dead()) {
                        //FIXME: should sleep ?
                        break; //renew cursor
                    }
                    // read all results so far, wait for more
                    //FIXME: should sleep ?
                } else {
                    $msg = $cursor->getNext();

                    if($msg[self::KEY_HANDLED]) {
                        continue;
                    }

                    //non-handled message, try to receive it
                    $msg = $this->_receiveMessageAtomic($queue, $collection, $msg['_id']);

                    if(null === $msg) {
                        continue; //meanwhile message has been handled already then ignore it
                    }

                    $iterator = new $classname(array($msg), $queue);
                    $message = $iterator->current();

                    if ($callback === null) {
                        return $message;
                    }

                    if (!$callback($message)) {
                        return $message;
                    }


                }
            }while (true);

        }while (true);
    }

}