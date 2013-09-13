<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueue\Controller\Plugin\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZendQueue\Controller\Plugin\Queue;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

class QueueFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @return Queue
     * @throws ServiceNotCreatedException if ServiceManager is not found in application service locator
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $services = $serviceLocator->getServiceLocator();

        $helper = new Queue();
        $helper->setServiceManager($services);

        return $helper;
    }
}