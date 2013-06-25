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


use MongoId;
use Zend\Stdlib\MessageInterface;
use ZendQueue\Exception;
use ZendQueue\Queue;
use ZendQueue\Adapter\Capabilities\AwaitCapableInterface;
use ZendQueue\Parameter\SendParameters;
use ZendQueue\Parameter\ReceiveParameters;
use ZendQueue\Adapter\Mongo\AbstractMongo;
use ZendQueue\Adapter\Capabilities\AwaitMessagesCapableInterface;

class MongoCappedCollection extends AbstractMongo implements AwaitMessagesCapableInterface
{

    /**
     * Default options
     *
     * @var array
     */
    protected $defaultOptions = array(
        'size'          => 1000000,
        'maxMessages'   => 100,
        'threshold'     => 10,
    );

    /**
     * Create a new queue
     *
     * @param  string  $name Queue name
     * @return boolean
     */
    public function createQueue($name)
    {
        $options = $this->getOptions();

        if(version_compare('1.4.0', phpversion('mongo')) < 0) {
            $queue = $this->mongoDb->createCollection($name, true, $options['size'], $options['maxMessages']);
        } else {
            $queue = $this->mongoDb->createCollection($name, array('capped' => true, 'size' => $options['size'], 'max' =>  $options['maxMessages']));
        }

        if ($queue) {
            for($i=0; $i < $options['maxMessages']; $i++){
                $queue->insert(array(self::KEY_HANDLE => true));
            }
            return true;
        } //else

        return false;
    }

    /**
     * Check if a queue exists
     *
     * @param  string $name Queue name
     * @return boolean
     */
    public function queueExists($name)
    {
        $collection = $this->mongoDb->selectCollection($name);
        $result = $collection->validate();
        return (isset($result['capped']) && $collection->count() > 0) ? true : false;
    }

    /**
     * Send a message to the queue
     *
     * @param  Queue $queue
     * @param  MessageInterface $message Message to send to the active queue
     * @param  SendParameters $params
     * @return MessageInterface
     * @throws Exception\QueueNotFoundException
     * @throws Exception\RuntimeException
     */
    public function sendMessage(Queue $queue, MessageInterface $message, SendParameters $params = null)
    {
        $options = $this->getOptions();

        $this->_cleanMessageInfo($queue, $message);

        $collection = $this->mongoDb->selectCollection($queue->getName());

        if ($options['threshold'] && $collection->count(array(self::KEY_HANDLE => true)) < $options['threshold']) {
            //FIXME: Exception should be explained in a better way
            throw new Exception\RuntimeException('Cannot send message: capped collection is full.');
        }

        $id = new MongoId();
        $msg = array(
            '_id'              => $id,
            self::KEY_CLASS    => get_class($message),
            self::KEY_CONTENT  => (string) $message->getContent(),
            self::KEY_METADATA => $message->getMetadata(),
            self::KEY_HANDLE  => false,
        );

        try {
            $collection->insert($msg);
        } catch (\Exception $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        $this->_embedMessageInfo($queue, $message, $id, $params ? $params->toArray() : array());

        return $message;
    }

    /**
     * Await for messages in the queue and receive them
     *
     * @param  Queue $queue
     * @param  Closure $callback
     * @param  ReceiveParameters $params
     * @return MessageInterface
     * @throws Exception\RuntimeException
     */
    public function awaitMessages(Queue $queue, \Closure $callback = null, ReceiveParameters $params = null)
    {
        $classname = $queue->getOptions()->getMessageSetClass();
        $collection = $this->mongoDb->selectCollection($queue->getName());

        //Outer loop: get a cursor
        while (true) {

            /**
             * If the query doesn't match any documents, MongoDB does not keep a cursor open server side and thus
             * the whole "tail" process never starts.
             *
             * That occurs when:
             * - capped collection is empty
             * - query criteria doesn't match any documents
             *
             * Solution:
             * - we use handled-message as dummy documents, furthermore
             *   create() inserts dummy documents when the collection is created to avoid empty collection at first use.
             *
             * - finally, to get a valid cursor but to avoid re-reading already handled message
             *   we shouldn't start reading from the beginnig of the collection, so we get the second-last document position
             *   then we setup the query to start from the next position.
             *
             * Therefore tailable cursor will start from the last document always.
             *
             * Inspired by
             * @link http://shtylman.com/post/the-tail-of-mongodb/
             *
             * @FIXME: classFilter isn't yet supported here
             *
             */

            //Obtain the second last position
            $cursor = $collection->find()->sort(array('_id' => -1));
            $cursor->skip(1);
            $secondLast = $cursor->getNext();

            if (!$secondLast) {
                throw new Exception\RuntimeException('Cannot get second-last position, maybe there are not enough documents within the collection');
            }

            //Setup tailable cursor
            $cursor = $this->_setupCursor($collection, null, array('_id' => array('$gt' => $secondLast['_id'])), array('_id', self::KEY_HANDLE));
            $cursor->tailable(true);
            $cursor->awaitData(true);

            //Inner loop: read results and wait for more
            while (true) {

                //We don't need sleeping because at beginning of each loop hasNext() will await.
                //If we are at the end of results, hasNext() blocks execution for a while,
                //after a timeout period (or if cursor dies) it does return as normal.
                if (!$cursor->hasNext()) {

                    // is cursor dead ?
                    if ($cursor->dead()) {
                        //TODO: if we get a dead cursor repeatedly, an infinte loop or a temporary CPU high load may occur
                        break; //go to the outer loop, obtaining a new cursor
                    }
                    //else, we read all results so far, wait for more

                } else {

                    $msg = $cursor->getNext();

                    //To avoid resource-consuming, we ignore handled message early
                    if($msg[self::KEY_HANDLE]) {
                        continue; //inner loop
                    }

                    //we got the _id of a non-handled message, try to receive it
                    $msg = $this->_receiveMessageAtomic($queue, $collection, $msg['_id']);

                    //if meanwhile message has been handled already then we ignore it
                    if(null === $msg) {
                        continue; //inner loop
                    }

                    //Ok, message received
                    $iterator = new $classname(array($msg), $queue);
                    $message = $iterator->current();

                    if ($callback === null) {
                        return $message;
                    }

                    if (!$callback($message)) {
                        return $message;
                    }

                }

            } //inner loop

        } //outer loop
    }

}