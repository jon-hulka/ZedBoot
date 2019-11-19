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
	 *   default - string to use on failed validation
	 *   default_calc - date value to use on failed validation (anything accepted by DateTime constructor will work)
	 *   min_range
	 *   max_range
	 *   timezone
	 *   format - any string accepted by DateTime->format() - default 'Y-m-d'
	 */
	public function applyFilter($value,Array $options,$flags=null)
	{
		$result=false;
		$dt=null;
		$format=null;
		$compareFormat=null;
		$dtCompare=null;
		$tz=null;
		if(array_key_exists('timezone',$options)) $tz=new \DateTimeZone($options['timezone']);
		$value=strval($value);
		$compareFormat='Y-m-d H:i:s.u';
		$format=array_key_exists('format',$options)?$options['format']:'Y-m-d';
		//DateTime accepts single-character strings, don't allow them
		if(strlen($value)>1)
		{
			try
			{
				$dt=new \DateTime($value,$tz);
			}
			catch(\Exception $e)
			{
				//Invalid strings such as 'zzz' will generate an exception
				$dt=null;
			}
			$errs= \DateTime::getLastErrors();
			//Reject invalid dates such as 'feb 31'
			if($errs['warning_count']>0 || $errs['error_count']>0) $dt=null;
		}
		if($dt!==null && array_key_exists('min_range',$options))
		{
			if($dtCompare===null) $dtCompare=$dt->format($compareFormat);
			$minDT=new \DateTime($options['min_range']);
			$errs=\DateTime::getLastErrors();
			//Reject invalid dates such as 'feb 31'
			if($errs['warning_count']>0 || $errs['error_count']>0) throw new \Exception('Invalid date/time specified for min_range option.');
			if($minDT->format($compareFormat)>$dtCompare) $dt=null;
		}
		if($dt!==null && array_key_exists('max_range',$options))
		{
			if($dtCompare===null) $dtCompare=$dt->format($compareFormat);
			$maxDT=new \DateTime($options['max_range']);
			$errs=\DateTime::getLastErrors();
			//Reject invalid dates such as 'feb 31'
			if($errs['warning_count']>0 || $errs['error_count']>0) throw new \Exception('Invalid date/time specified for max_range option.');
			if($maxDT->format($compareFormat)<$dtCompare) $dt=null;
		}
		if($dt===null)
		{
			if(array_key_exists('default',$options))
			{
				$result=$options['default'];
			}
			else if(array_key_exists('default_calc',$options))
			{
				$dt=new \DateTime($options['default_calc']);
				$errs=\DateTime::getLastErrors();
				if($errs['warning_count']>0 || $errs['error_count']>0) throw new \Exception('Invalid date/time specified for default_calc option.');
				$result=$dt->format($format);
			}
		}
		else
		{
			$result=$dt->format($format);
		}

		return $result;
	}
}
