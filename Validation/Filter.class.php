<?php
/**
 * Class Filter | ZedBoot/Validation/Filter.class.php
 * @license     GNU General Public License, version 3
 * @package     Validation
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2019 Jonathan Hulka
 */

/**
 * Implementation of FilterInterface using PHP's filter_var with a few more bells and whistles.
 */
namespace ZedBoot\Validation;
use \ZedBoot\Error\ZBError as Err;
class Filter implements \ZedBoot\Validation\FilterInterface
{
	/**
	 * Additional elements allowed and/or required by filter definitions (not in the options array):
	 * 'name' (required): user-friendly name
	 * 'help' (required): text telling user what is expected in case of failure
	 * 'required' (optional default false): boolean indicates whether value is required to be present
	 * 'discard_empty' (optional default false): boolean indicates whether empty strings should be discarded from the result
	 * 'empty_as_null' (optional default false): boolean indicates whether empty strings should be converted to null
	 * custom handlers can be assigned to any of the filter types
	 * If 'required' and 'discard_empty' are both set, an empty string for that field will cause an error status
	 */
	protected
		$definitions,
		$handlers;
	/**
	 * flags can be specified in a filter definition array, or in its 'options' array, if it is in both, the outer version will override the 'options' version
	 * @param Array $definitions as passed to filter_var_array with additional elements as described above
	 * @param Array $customHandlers [<filter name>=><\ZedBoot\Validation\FilterHandlerInterface instance>,...]
	 */
	public function __construct(
		Array $definitions,
		Array $customHandlers=[]
	)
	{
		$this->handlers=$customHandlers;
		$badDefs=[];
		$errs=[];
		$required=['filter','name','help'];
		//Find any errors in handlers and definitions
		foreach($this->handlers as $k=>$h) if(!array_key_exists($k,$badDefs))
		{
			if(!is_object($h) || !($h instanceof \ZedBoot\Validation\FilterHandlerInterface))
			{
				$errs[]='$customHandlers['.$k.']: expected \\ZedBoot\\Validation\\FilterHandlerInterface, got '.$this->getTypeString($h);
			}
		}
		foreach($definitions as $k=>$def) if(!array_key_exists($k,$badDefs))
		{
			$missing=[];
			if(!is_array($def))
			{
				$badDefs[$k]=$k;
				$errs[]='$definitions['.$k.']: expected Array, got '.$this->getTypeString($def);
			}
			else foreach($required as $req) if(!array_key_exists($req,$def)) $errs[]='Missing $definitions['.$k.']['.$req.']';
		}
		foreach($definitions as $k=>$def) if(!array_key_exists($k,$badDefs))
		{
			$filter=$def['filter'];
			if(!array_key_exists('options',$def)) $def['options']=[];
			if(!array_key_exists('flags',$def)) $def['flags']=null;
			if(array_key_exists($filter,$this->handlers))
			{
				unset($def['filter']);
				$def['filter_handler']=$this->handlers[$filter];
			}
			else if(!is_int($def['filter']))
			{
				$badDefs[$k]=$k;
				$errs[]='$definitions['.$k.'][\'filter\']: non-integer filter type with no custom handler. Expected int or $customHandlers key, got '.$this->getTypeString($def['filter']);
			}
			if(!is_string($def['name']))
			{
				$badDefs[$k]=$k;
				$errs[]='$definitions['.$k.'][\'name\']: expected string, got '.$this->getTypeString($def['name']);
			}
			if(!is_string($def['help']))
			{
				$badDefs[$k]=$k;
				$errs[]='$definitions['.$k.'][\'help\']: expected string, got '.$this->getTypeString($def['help']);
			}
			$this->definitions[$k]=$def;
		}
		if(count($errs)>0) throw new \Exception(implode(PHP_EOL,$errs));
	}
	protected function getTypeString($v)
	{
		return is_scalar($v)
			? gettype($v).'('.json_encode($v).')'
			: (is_object($v)
				? get_class($v)
				: gettype($v));
	}
	public function validate(Array $parameters)
	{
		$ok=true;
		$result=false;
		$this->messages=[];
		$vs=[];
		//Only apply filters to parameters that are present
		$toApply=[];
		if($ok) foreach($this->definitions as $k=>$def)
		{
			if(array_key_exists($k,$parameters))
			{
				$parameters[$k]=strval($parameters[$k]);
				if(strlen($parameters[$k])===0 && !empty($def['discard_empty'])) unset($parameters[$k]);
			}
			if(array_key_exists($k,$parameters))
			{
				$toApply[$k]=$def;
			}
			else if(!empty($def['required']))
			{
				$ok=false;
				$this->messages[$k]='Missing required field: '.$def['name'];
			}
		}
		if($ok) foreach($toApply as $k=>$def)
		{
			$in=$parameters[$k];
			$out=null;
			if(strlen($in)>0 || empty($def['empty_as_null']))
			{
				if(array_key_exists('filter',$def))
				{
					//Options and flags must be nested in the $options parameter
					$options=['options'=>$def['options']];
					if($def['flags']!==null) $options['flags']=$def['flags'];
					$out=filter_var($in,$def['filter'],$options);
				}
				else
				{
					//This one has a custom handler
					$out=$def['filter_handler']->applyFilter($in,$def['options'],$def['flags']);
				}
				if($out===false)
				{
					$ok=false;
					$this->messages[$k]=$def['help'];
				}
			}
			//else input is empty string and empty_as_null is specified - $out is already null
			$vs[$k]=$out;
		}
		if($ok) $result=$vs;
		return $result;
	}
	public function getMessages()
	{
		return $this->messages;
	}
}
