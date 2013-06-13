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

use Zend\ServiceManager\AbstractPluginManager;
use ZendQueue\Adapter\AdapterInterface;
use ZendQueue\Exception;

/**
 * Plugin manager implementation for queue adapters
 *
 * Enforces that adapters retrieved are instances of
 * AdapterInterface. Additionally, it registers a number of default
 * adapters available.
 */
class AdapterPluginManager extends AbstractPluginManager
{
    /**
     * Default set of adapters
     *
     * @var array
     */
    protected $invokableClasses = array(
        'arrayadapter'   => 'ZendQueue\Adapter\ArrayAdapter',
        'db'             => 'ZendQueue\Adapter\Db',
        'mongocapped'    => 'ZendQueue\Adapter\MongoCappedColletion',
        'mongo'          => 'ZendQueue\Adapter\MongoColletion',
        'null'           => 'ZendQueue\Adapter\Null',
    );

    /**
     * Do not share by default
     *
     * @var array
    */
    protected $shareByDefault = false;

    /**
     * Validate the plugin
     *
     * Checks that the adapter loaded is an instance of AdapterInterface.
     *
     * @param  mixed $plugin
     * @return void
     * @throws Exception\RuntimeException if invalid
     */
    public function validatePlugin($plugin)
    {
        if ($plugin instanceof AdapterInterface) {
            // we're okay
            return;
        }

        throw new Exception\RuntimeException(sprintf(
            'Plugin of type %s is invalid; must implement %s\AdapterInterface',
            (is_object($plugin) ? get_class($plugin) : gettype($plugin)),
            __NAMESPACE__
        ));
    }
}