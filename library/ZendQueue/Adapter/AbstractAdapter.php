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

use Traversable;
use Zend\Stdlib\ArrayUtils;
use ZendQueue\Exception;
use ZendQueue\Queue;
use Zend\Stdlib\Message;
use Zend\Stdlib\ParameterObjectInterface;
use Zend\Stdlib\Parameters;

/**
 * Class for connecting to queues performing common operations.
 *
 */
abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * User-provided options
     *
     * @var array
     */
    protected $_options = array();

    /**
     * Internal array of queues to save on lookups
     *
     * @var array
     */
    protected $_queues = array();

    /**
     * Constructor.
     *
     * $options is an array of key/value pairs or an instance of Traversable
     * containing configuration options.  These options are common to most adapters:
     *
     * @param  array|Traversable $options An array having configuration data
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options)
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        /*
         * Verify that adapter parameters are in an array.
         */
        if (!is_array($options)) {
            throw new Exception\InvalidArgumentException('Adapter options must be an array or Traversable object');
        }


        $adapterOptions = array();
        $driverOptions  = array();

        // Normalize the options and merge with the defaults
        if (array_key_exists('options', $options)) {
            if (!is_array($options['options'])) {
                throw new Exception\InvalidArgumentException("Configuration array 'options' must be an array");
            }

            // Can't use array_merge() because keys might be integers
            foreach ($options['options'] as $key => $value) {
                $adapterOptions[$key] = $value;
            }
        }
        if (array_key_exists('driverOptions', $options)) {
            // can't use array_merge() because keys might be integers
            foreach ((array)$options['driverOptions'] as $key => $value) {
                $driverOptions[$key] = $value;
            }
        }
        $this->_options = array_merge($this->_options, $options);
        $this->_options['options']       = $adapterOptions;
        $this->_options['driverOptions'] = $driverOptions;

    }

    protected function _buildMessageInfo($id, $queue, $options = null)
    {
        return array(
            'messageId' => $id,
            'queue'     => $queue instanceof Queue ? $queue->getName() : (string) $queue,
            'adapter'   => get_class($this),
            'options'   => $options instanceof Parameters ? $options->toArray() : (array) $options,
        );
    }


    protected function _embedMessageInfo(Queue $queue, Message $message, $id, $options = null)
    {
        $message->setMetadata($queue->getOptions()->getMessageMetadatumKey(), $this->_buildMessageInfo($id, $queue, $options));
    }

    protected function _extractMessageInfo(Queue $queue, Message $message)
    {
       return $message->getMetadata($queue->getOptions()->getMessageMetadatumKey());
    }

    protected function _cleanMessageInfo(Queue $queue, Message $message)
    {
        $metadatumKey = $queue->getOptions()->getMessageMetadatumKey();
        if ($message->getMetadata($metadatumKey, null)) {
            $message->setMetadata($metadatumKey, null);
        }
    }

    /**
     * Returns the configuration options in this adapter.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    public function getAvailableReceiveParams()
    {
        return array();
    }

    public function getAvailableSendParams()
    {
        return array();
    }

}
