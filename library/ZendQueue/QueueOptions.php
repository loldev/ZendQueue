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

use Zend\Stdlib\AbstractOptions;

class QueueOptions extends AbstractOptions
{

    protected $defaultQueueName = 'default';

    protected $adapterNamespace = '\ZendQueue\Adapter';

    protected $driverOptions = array();

    protected $adapterOptions = array();


    /**
     * The default message class
     *
     * @var string
     */
    protected $messageClass = '\ZendQueue\Message\Message';

    /**
     * default message set (iterator) class
     *
     * @var string
     */
    protected $messageSetClass = '\ZendQueue\Message\MessageIterator';

    /**
     * Metadata key name used to inject queue info into message
     *
     * @var string
     */
    protected $messageMetadatumKey = '__queue';


    /**
     * When await capability isn't available,
     * if this flag is true polling will be used instead,
     * else an exception will be trown
     *
     * @var bool
     */
    protected $enableAwaitEmulation = true;

    /**
     * Used only if enableAwaitEmulation is true
     *
     * @var int
     */
    protected $pollingInterval = 1;


    public function setDefaultQueueName($name)
    {
        $this->defaultQueueName = (string) $name;
        return $this;
    }

    public function getDefaultQueueName()
    {
        return $this->defaultQueueName;
    }

    public function setAdapterNamespace($namespace)
    {
        $this->adapterNamespace = (string) $namespace;
        return $this;
    }

    public function getAdapterNamespace()
    {
        return $this->adapterNamespace;
    }

    public function setDriverOptions(array $options)
    {
        $this->driverOptions = $options;
        return $this;
    }

    public function getDriverOptions()
    {
        return $this->driverOptions;
    }

    public function setAdapterOptions(array $options)
    {
        $this->adapterOptions = $options;
        return $this;
    }

    public function getAdapterOptions()
    {
        return $this->adapterOptions;
    }

    public function setMessageClass($class)
    {
        $this->messageClass = (string) $class;
        return $this;
    }

    public function getMessageClass()
    {
        return $this->messageClass;
    }

    public function setMessageSetClass($class)
    {
        $this->messageSetClass = (string) $class;
        return $this;
    }

    public function getMessageSetClass()
    {
        return $this->messageSetClass;
    }

    public function setMessageMetadatumKey($keyName)
    {
        $this->messageMetadatumKey = (string) $keyName;
        return $this;
    }

    public function getMessageMetadatumKey()
    {
        return $this->messageMetadatumKey;
    }

    /**
     * @param true $flag
     * @return \ZendQueue\QueueOptions
     */
    public function setEnableAwaitEmulation($flag)
    {
        $this->enableAwaitEmulation = (bool) $flag;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getEnableAwaitEmulation()
    {
        return $this->enableAwaitEmulation;
    }

    public function setPollingInterval($sec)
    {
        $this->pollingInterval = (int) $sec;
        return $this;
    }

    public function getPollingInterval()
    {
        return $this->pollingInterval;
    }


}