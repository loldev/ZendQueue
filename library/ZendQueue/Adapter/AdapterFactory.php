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

use ZendQueue\Exception;

abstract class AdapterFactory
{
    /**
     * Plugin manager for loading adapters
     *
     * @var null|AdapterPluginManager
     */
    protected static $adapters = null;

    /**
     * Instantiate a queue adapter
     *
     * @param  array|Traversable $cfg
     * @return AdapterInterface
     * @throws Exception\InvalidArgumentException
     */
    public static function factory($cfg)
    {
        if ($cfg instanceof \Traversable) {
            $cfg = ArrayUtils::iteratorToArray($cfg);
        }

        if (!is_array($cfg)) {
            throw new Exception\InvalidArgumentException(
                'The factory needs an associative array '
                . 'or a Traversable object as an argument'
            );
        }

        if (!isset($cfg['adapter'])) {
            throw new Exception\InvalidArgumentException('Missing "adapter"');
        }

        if ($cfg['adapter'] instanceof AdapterInterface) {
            // $cfg['adapter'] is already an adapter object
            $adapter = $cfg['adapter'];
        } else {
            $adapter = static::getAdapterPluginManager()->get($cfg['adapter']);
        }

        if (isset($cfg['options'])) {
            if (!is_array($cfg['options'])) {
                throw new Exception\InvalidArgumentException('"options" must be an array, ' . gettype($cfg['options']) . ' given.');
            }
            $adapter->setOptions($cfg['options']);
        }

        return $adapter;
    }

    /**
     * Get the adapter plugin manager
     *
     * @return AdapterPluginManager
     */
    public static function getAdapterPluginManager()
    {
        if (static::$adapters === null) {
            static::$adapters = new AdapterPluginManager();
        }
        return static::$adapters;
    }

    /**
     * Change the adapter plugin manager
     *
     * @param  AdapterPluginManager $adapters
     * @return void
     */
    public static function setAdapterPluginManager(AdapterPluginManager $adapters)
    {
        static::$adapters = $adapters;
    }

    /**
     * Resets the internal adapter plugin manager
     *
     * @return void
     */
    public static function resetAdapterPluginManager()
    {
        static::$adapters = null;
    }
}
