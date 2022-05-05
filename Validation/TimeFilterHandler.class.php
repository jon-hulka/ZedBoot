<?php
/**
 * Class TimeFilterHandler | ZedBoot/Validation/TimeFilterHandler.class.php
 * @license     GNU General Public License, version 3
 * @package     Validation
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2019 Jonathan Hulka
 */

namespace ZedBoot\Validation;
class TimeFilterHandler implements \ZedBoot\Validation\FilterHandlerInterface
{
	/*
	 * options (in the options array):
	 *   min_range
	 *   max_range
	 *   format - any string accepted by DateTime->format() - default 'H:i:s'
	 */
	public function applyFilter($value, string $name, array $options, array $flags = null) : array
	{
		$result = ['status' => 'error', 'message' => 'System error: unexpected failure in ' . get_class($this) . '.'];
		$ok = true;
		$dt = null;
		$compareFormat = 'H:i:s.u';
		$format = array_key_exists('format', $options) ? $options['format'] : 'h:i:s';
		$parts = null;
		//date_parse() won't handle relative formats - 'back of', 'front of' etc
		//They don't make a lot of sense to most people
		//'noon' and 'midnight' will still work
//To do: think about allowing 'now' --- check strtolower(substring($value,0,3))=='now'
//if so, parse using DateTime to allow relative times such as now +1hour
//To do: think about whether to parse out and apply the 'relative' part of $parts (try date_parse('01:01:01 +1hour +2minute +3second'))
		//DateTime accepts single-character strings, so don't allow them
		if($ok) $ok = strlen($value) > 1;
		if($ok)
		{
			$parts = date_parse($value);
			$ok =
				$parts['warning_count'] === 0 && $parts['error_count'] === 0 &&
				//We don't want any date elements
				$parts['day'] === false && $parts['month'] === false && $parts['year'] === false &&
				//If hour is present, minute, second, and fraction will be there
				$parts['hour'] !== false;
		}
		if($ok)
		{
			$f = strval($parts['fraction']);
			$f = strlen($f) > 2 ? substr($f,1) : '';
			$dt = new \DateTime(sprintf('%1$02d:%2$02d:%3$02d', $parts['hour'], $parts['minute'], $parts['second']) . $f);
		}
		if($ok && array_key_exists('min_range', $options))
		{
			$minDT = new \DateTime($options['min_range']);
			$errs = \DateTime::getLastErrors();
			//Reject invalid dates such as 'feb 31'
			if($errs['warning_count'] > 0 || $errs['error_count'] > 0) throw new \Exception('Invalid date/time specified for min_range option.');
			if($minDT->format($compareFormat) > $dt->format($compareFormat)) $ok = false;
		}
		if($ok && array_key_exists('max_range', $options))
		{
			$maxDT = new \DateTime($options['max_range']);
			$errs = \DateTime::getLastErrors();
			//Reject invalid dates such as 'feb 31'
			if($errs['warning_count'] > 0 || $errs['error_count'] > 0) throw new \Exception('Invalid date/time specified for max_range option.');
			if($maxDT->format($compareFormat) < $dt->format($compareFormat)) $ok = false;
		}
		if($ok)
		{
			$result = ['status' => 'success', 'value' => $dt->format($format)];
		}
		else $result = ['status' => 'error'];
		return $result;
	}
}
