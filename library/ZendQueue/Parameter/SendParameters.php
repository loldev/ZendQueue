<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Queue
 */

namespace ZendQueue\Parameter;

use Zend\Stdlib\Parameters;
class SendParameters extends Parameters
{

    const SCHEDULE = 'schedule';
    const INTERVAL = 'interval';
    const TIMEOUT  = 'timeout';


    public function setScheduling($scheduleTime = null, $interval = null)
    {
        if($scheduleTime !== null) {
            $this->set(self::SCHEDULE, (int) $scheduleTime);
        }

        if($interval !== null) {
            $this->set(self::INTERVAL, (int) $interval);
        }

        return $this;
    }

    public function getSchedule()
    {
        return $this->get(self::SCHEDULE, false);
    }

    public function getInterval()
    {
        return $this->get(self::INTERVAL, false);
    }

    public function setTimeout($timeout)
    {
        $this->set(self::TIMEOUT, (int) $timeout);
        return $this;
    }

    public function getTimeout()
    {
        return $this->get(self::TIMEOUT, null);
    }



}