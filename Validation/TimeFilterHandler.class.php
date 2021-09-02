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
	 *   default - string to use on failed validation
	 *   default_calc - value to use on failed validation (anything accepted by DateTime constructor will work)
	 *   min_range
	 *   max_range
	 *   timezone
	 *   format - any string accepted by DateTime->format() - default 'H:i:s'
	 */
	public function applyFilter($value, string $name, array $options, array $flags=null)
	{
		$result=false;
		$dt=null;
		$tz=null;
		$format=null;
		$compareFormat=null;
		if(array_key_exists('timezone',$options)) $tz=new \DateTimeZone($options['timezone']);
		$type=empty($options['type'])?'':$options['type'];
		$value=strval($value);
		//DateTime accepts single-character strings, so don't allow them
		$compareFormat='H:i:s.u';
		$format=array_key_exists('format',$options)?$options['format']:'h:i:s';
		$h=false;
		$m=false;
		$s=false;
		//date_parse() won't handle relative formats - 'back of', 'front of' etc
		//They don't make a lot of sense to most people
		//'noon' and 'midnight' will still work
//To do: think about allowing 'now' --- check strtolower(substring($value,0,3))=='now'
//if so, parse using DateTime to allow relative times such as now +1hour
//To do: think about whether to parse out and apply the 'relative' part of $parts (try date_parse('01:01:01 +1hour +2minute +3second'))
		$parts=date_parse($value);
		if($parts['day']===false && $parts['month']===false && $parts['year']===false)
		{
			//This is for times only, if a date component is present it is invalid
			if($parts!==false && $parts['warning_count']==0 && $parts['error_count']==0)
			{
				$h=$parts['hour'];
				$m=$parts['minute'];
				$s=$parts['second'];
				$f=$parts['fraction'];
			}
			if($parts['hour']!==false)
			{
				//If hour is present, minute, second, and fraction will be there
				$f=strval($parts['fraction']);
				$f=strlen($f)>2?substr($f,1):'';
				$dt=new \DateTime(sprintf('%1$02d:%2$02d:%3$02d',$parts['hour'],$parts['minute'],$parts['second']).$f,$tz);
			}
		}
		$dtCompare=null;
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
		if($dt!==null)
		{
			$result=$dt->format($format);
		}
		else
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
		return $result;
	}

	public function getMessage() : ?string { return null; }
}
