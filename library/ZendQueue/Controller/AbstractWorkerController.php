<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueue\Controller;

use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\Http\Request;
use Zend\Http\Client;
use Zend\Stdlib\MessageInterface;
use Zend\Serializer\Adapter\AdapterInterface as SerializerAdapter;
use Zend\Serializer\Serializer;
use Zend\View\Model\ConsoleModel;
use ZendQueue\Exception;
use ZendQueue\Queue;
use ZendQueue\Parameter\ReceiveParameters;
use ZendQueue\Controller\Message\Forward;
use ZendQueue\Controller\Message\WorkerMessageInterface;
use ZendQueue\Controller\Message\WorkerExit;


abstract class AbstractWorkerController extends AbstractController
{

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var ReceiveParameters
     */
    protected $recvParams;

    /**
     * @var SerializerAdapter
     */
    protected $serializer;

    /**
     * @var bool
     */
    protected $await;


    public function __construct(Queue $queue, ReceiveParameters $recvParams = null)
    {
        $this->queue = $queue;
        $this->recvParams = $recvParams;
    }

    /**
     * @param SerializerAdapter $adapter
     * @return AbstractWorkerController
     */
    public function setSerializer(SerializerAdapter $adapter)
    {
        $this->serializer = $adapter;
        return $this;
    }

    /**
     * @return SerializerAdapter
     */
    public function getSerializer()
    {
        if (null === $this->serializer) {
            $this->serializer = Serializer::factory('PhpSerialize');
        }
        return $this->serializer;
    }

    /**
     * Execute the request
     *
     * @param  MvcEvent $e
     * @return mixed
     * @throws Exception\DomainException
     */
    public function onDispatch(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();
        if (!$routeMatch) {
            /**
             * @todo Determine requirements for when route match is missing.
             *       Potentially allow pulling directly from request metadata?
             */
            throw new \Zend\Mvc\Exception\DomainException('Missing route matches; unsure how to retrieve action');
        }

        $action = $routeMatch->getParam('action', null);

        switch ($action) {
            case 'execute':
                $message = $routeMatch->getParam('message', null);

                if(is_string($message)) {
                    $message = $this->getSerializer()->unserialize($message);
                }

                if($message) {
                    $result = $this->execute($message);
                } else {
                    $result = $this->createConsoleErrorModel('Missing or invalid message');
                }
                break;

            case 'await':
                $result = $this->await();
                break;

            default:
                $result = $this->createConsoleErrorModel('Invalid action');
        }


        $e->setResult($result);

        return $result;
    }

    /**
     * Execute a job
     *
     * @param MessageInterface $message
     * @return mixed
     * @throws Exception\InvalidMessageException
     */
    public function execute(MessageInterface $message)
    {
        switch (true) {

            case $message instanceof Forward:
                $return = $this->forward()->dispatch($message->getContent(), $message->getMetadata());
                break;


            case $message instanceof Request:
                $client = new Client();
                $return = $client->send($message);
                break;

            case $message instanceof WorkerMessageInterface:
                $return = $this->workerMessageHandler($message);
                break;

            default:
                throw new Exception\InvalidMessageException('Message not supported.');
        }


        return $return;
    }



    /**
     * Wait for an incoming message
     *
     * @throws Exception
     * @return boolean|multitype:
     */
    public function await()
    {
        $this->await = true;

        $queue = $this->queue;

        $handler = function(MessageInterface $message) use($queue) {

            //TODO handle response
            $this->execute($message);

            //TBD: right place to delete message?
            if ($queue->canDeleteMessage()) {
                $queue->deleteMessage($message);
            }

            return $this->await;
        };

        $handler->bindTo($this);

        while($this->await) {
            try {
                $queue->await($this->recvParams, $handler);
            } catch (\Exception $e) {
                throw $e; //TODO: how handle errors?
            }
        }

        return array();
    }


    protected function workerMessageHandler(WorkerMessageInterface $message)
    {
        switch (true) {
            case $message instanceof WorkerExit:
                if($this->await) {
                    $this->await = false;
                }
                break;
            //TODO: handle other messages ?

        }

        return array();
    }


    /**
     * Create a console view model representing an error
     *
     * @return ConsoleModel
     */
    protected function createConsoleErrorModel($errorMsg)
    {
        $viewModel = new ConsoleModel();
        $viewModel->setErrorLevel(1);
        $viewModel->setResult($errorMsg);
        return $viewModel;
    }

}