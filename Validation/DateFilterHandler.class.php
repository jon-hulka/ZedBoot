<?php
/**
 * Class DateFilterHandler | ZedBoot/Validation/DateFilterHandler.class.php
 * @license     GNU General Public License, version 3
 * @package     Validation
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2019 Jonathan Hulka
 */

namespace ZedBoot\Validation;
class DateFilterHandler implements \ZedBoot\Validation\FilterHandlerInterface
{
	/**
	 * options:
	 *   min_range
	 *   max_range
	 *   format - any string accepted by DateTime->format() - default 'Y-m-d'
	 */
	public function applyFilter(string $value, string $name, array $options, array $flags=null) : array
	{
		$result = ['status' => 'error', 'message' => 'System error: unexpected failure in ' . get_class($this) . '.'];
		$ok = true;
		$dt = null;
		$compareFormat = 'Y-m-d H:i:s.u';
		$format = array_key_exists('format',$options) ? $options['format'] : 'Y-m-d';
		//DateTime accepts single-character strings, don't allow them
		if($ok) $ok = strlen($value) > 1;
		if($ok)
		{
			try
			{
				$dt = new \DateTime($value);
			}
			catch(\Exception $e)
			{
				//Invalid strings such as 'zzz' will generate an exception
				$dt = null;
			}
			$errs = \DateTime::getLastErrors();
			//Reject invalid dates such as 'feb 31'
			$ok = $errs['warning_count'] === 0 && $errs['error_count'] === 0;
		}
		if($ok && array_key_exists('min_range', $options))
		{
			$minDT = new \DateTime($options['min_range']);
			$errs = \DateTime::getLastErrors();
			//Reject invalid dates such as 'feb 31'
			if($errs['warning_count'] > 0 || $errs['error_count'] > 0) throw new \Exception('Invalid date/time specified for min_range option.');
			if($minDT->format($compareFormat) > $dt->format($compareFormat)) $ok = false;
		}
		if($dt!==null && array_key_exists('max_range',$options))
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
