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
    const REPEATING_INTERVAL = 'repeatingInterval';


    /**
     * Set message schedule time
     *
     * $scheduleTime must be an unix timestamp or null (to disable this feature)
     * If $scheduleTime is setted, the message will be "invisibile" to readers
     * until the scheduled time.
     *
     * @param string $scheduleTime
     * @throws Exception\InvalidArgumentException
     * @return \ZendQueue\Parameter\SendParameters
     */
    public function setSchedule($scheduleTime = null)
    {
        if (($scheduleTime !== null) && !is_string($scheduleTime)) {
            throw new Exception\InvalidArgumentException('$scheduleTime must be a int or null');
        }
        $this->set(self::SCHEDULE, $scheduleTime);
        return $this;
    }

    /**
     * Get message schedule time
     *
     * @see setSchedule()
     *
     * @return int|null
     */
    public function getSchedule()
    {
        return $this->get(self::SCHEDULE, false);
    }

    /**
     * Set message repeating interval
     *
     * $repeatingInterval must be an int (seconds) or null (to disable this feature)
     * If $repeatingInterval is setted, each time the message is acknowleged by a reader
     * the message will be auto scheduled at current time plus interval seconds
     *
     * @param string $repeatingInterval
     * @throws Exception\InvalidArgumentException
     * @return \ZendQueue\Parameter\SendParameters
     */
    public function setRepeatingInterval($repeatingInterval = null)
    {
        if (($repeatingInterval !== null) && !is_string($repeatingInterval)) {
            throw new Exception\InvalidArgumentException('$repeatingInterval must be a int or null');
        }
        $this->set(self::REPEATING_INTERVAL, $repeatingInterval);
        return $this;
    }

    /**
     * Get message repeating interval
     *
     * @see setRepeatingInterval()
     *
     * @return int|null
     */
    public function getRepeatingInterval()
    {
        return $this->get(self::REPEATING_INTERVAL, false);
    }

}