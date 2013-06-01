<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueue\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use ZendQueue\Parameter\SendParameters;
use Zend\Http\Request;
use ZendQueue\Controller\Message\Forward;

class Queue extends AbstractPlugin implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var \ZendQueue\Queue
     */
    protected $queue;

    public function getQueue()
    {
        return $this->queue;
    }

    public function __invoke($queue)
    {
        if (is_string($queue)) {
            $queue = $this->getServiceLocator()->get($queue);
        }

        if (!$queue instanceof \ZendQueue\Queue) {
            throw new \InvalidArgumentException('Invalid $queue: must be a string or an instace of \ZendQueue\Queue');
        }

        $this->queue = $queue;

        return $this;
    }


    public function send($message, SendParameters $params = null)
    {
        return $this->queue->send($message, $params);
    }

    /**
     * Send a forward message to another controller
     *
     * @param  string $name Controller name; either a class name or an alias used in the DI container or service locator
     * @param  null|array $params Parameters with which to seed a custom RouteMatch object for the new controller
     * @return Forward
     */
    public function forward($name, array $params = null, SendParameters $sendParams = null)
    {
        $message = new Forward();
        $message->setContent($name);
        if($params !== null) {
            $message->setMetadata($params);
        }

        $this->queue->send($message, $sendParams);
        return $message;
    }

    public function http($request, SendParameters $sendParams = null)
    {
        if(is_string($request)) {
            $req = new Request();
            $req->setUri($request);
            $request = $req;
        }

        if (!$request instanceof Request) {
            throw new \InvalidArgumentException('Invalid $request: must be an URI as string or an instace of \Zend\Http\Request');
        }

        $this->queue->send($request, $sendParams);
        return $message;
    }
}