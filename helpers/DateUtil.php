<?php
/**
 * DateUtil - helper to work with date
 */
class DateUtil {
	const DB_DATE_FORMAT = 'Y-m-d';
	const DB_TIME_FORMAT = 'H:i:s';
	const DB_FULL_FORMAT = 'Y-m-d H:i:s';
	const HUMAN_DATE_FORMAT = 'd.m.Y';
	const HUMAN_FULL_FORMAT = 'd.m.Y H:i';

	/**
	 * @param string|DateTime $date
	 * @return DateTime
	 */
	public static function getDateTime($date = null) {
		if (!($date instanceof DateTime)) {
			$date = ($date == null) ? new DateTime('now') : new DateTime($date);
		}
		return $date;
	}

	/**
	 * Get start time of day
	 * <br>
	 * Example: 2012-11-14 00:00:00
	 * @param string|DateTime $date
	 * @return DateTime
	 */
	public static function startOfDay($date) {
		$date = self::getDateTime($date);
		return $date->setTime(0,0,0);
	}

	/**
	 * Get end time of day
	 * <br>
	 * Example: 2012-11-14 23:59:59
	 * @param string|DateTime $date
	 * @return DateTime
	 */
	public static function endOfDay($date) {
		$date = self::startOfDay($date);
		$date->add(new DateInterval('P1D'));
		return $date->sub(new DateInterval('PT1S'));
	}

	/**
	 * Difference in days between $date1 and $date2.
	 * <br>
	 * Can be positive or negative number.
	 * @param string|DateTime $date1
	 * @param string|DateTime $date2
	 * @return int
	 */
	public static function diff($date1, $date2) {
		$date1 = self::getDateTime($date1);
		$date2 = self::getDateTime($date2);
		return (int) $date1->diff($date2)->format('%R%a');
	}

	/**
	 * @param string|DateTime $date
	 * @param string $modify format accepted by strtotime()
	 * @return DateTime
	 */
	public static function sub($date, $modify) {
		$date = self::getDateTime($date);
		return $date->modify($modify);
	}

	/**
	 * @param string|DateTime $date
	 * @param string $modify format accepted by strtotime()
	 * @return DateTime
	 */
	public static function add($date, $modify) {
		return self::sub($date, $modify);
	}

	/**
	 * Period as array.
	 * @param string|DateTime $date1
	 * @param string|DateTime $date2
	 * @return array
	 */
	public static function periodAsArray($date1, $date2) {
		$result = array();
		$date1 = self::getDateTime($date1);
		$date2 = self::getDateTime($date2);
		$interval = new DateInterval('P1D');
		$date2->add($interval);
		$period = new DatePeriod($date1, $interval, $date2);
		foreach ($period as $day) {
			$result[] = $day->format(DateUtil::DB_DATE_FORMAT);
		}

		return $result;
	}

	/**
	 * first day of this month
	 * @param string|DateTime $date
	 * @return DateTime
	 */
	public static function firstDayMonth($date) {
		return self::getDateTime($date)->modify('first day of this month');
	}

	/**
	 * last day of this month
	 * @param string|DateTime $date
	 * @return DateTime
	 */
	public static function lastDayMonth($date) {
		return self::getDateTime($date)->modify('last day of this month');
	}

	/**
	 * first day of next month
	 * @param string|DateTime $date
	 * @return DateTime
	 */
	public static function firstDayNextMonth($date) {
		return self::getDateTime($date)->modify('first day of next month');
	}

	/**
	 * last day of next month
	 * @param string|DateTime $date
	 * @return DateTime
	 */
	public static function lastDayNextMonth($date) {
		return self::getDateTime($date)->modify('last day of next month');
	}

	/**
	 * Get first day of every month
	 * @param string|DateTime $date1
	 * @param string|DateTime $date2
	 * @return array
	 */
	public static function firstDayOfEveryMonth($date1, $date2) {
		$period = self::periodAsArray($date1, $date2);
		$result = array();
		foreach ($period as $date) {
			$firstDay = self::firstDayMonth($date)->format(self::DB_DATE_FORMAT);
			$result[$firstDay] = $firstDay;
		}

		return array_values($result);
	}

	/**
	 * Generate list of days for date range from $minDate till $maxDate
	 *
	 * @param string|null $minDate
	 * @param string|null $maxDate
	 * @return array like ['2012-01-01', '2012-01-02', ...]
	 */
	public static function rangeDays($minDate = null, $maxDate = null) {
		if ($minDate === null) {
			$minDate = date(self::DB_DATE_FORMAT, time() - 30 * DAY);
		}
		if ($maxDate === null) {
			$maxDate = DateUtil::today();
		}

		$minTime = strtotime($minDate);
		$maxTime = strtotime($maxDate) + DAY;

		$result = array();
		$lastDate = '';
		$time = $minTime;
		while ($time < $maxTime) {
			$date = date('Y-m-d', $time);
			if ($date == $lastDate) { // be prepared for spring/autumn +1/-1 hour change
				$time += HOUR;
				continue;
			}

			$result[] = $date;
			$lastDate = $date;

			$time += DAY;
		}

		return $result;
	}

	/**
	 * Get today date
	 *
	 * @param string $format
	 *
	 * @return string
	 */
	public static function today($format = null) {
		if ($format === null) {
			$format = DateUtil::DB_DATE_FORMAT;
		}

		return DateUtil::getDateTime()->format($format);
	}

	/**
	 * @param string|DateTime $date
	 * @return string
	 */
	public static function getDateDbFormat($date = null) {
		$date = self::getDateTime($date);
		return $date->format(self::DB_DATE_FORMAT);
	}
}
