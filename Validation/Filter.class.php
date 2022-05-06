<?php
/**
 * Class Filter | ZedBoot/Validation/Filter.class.php
 * @license     GNU General Public License, version 3
 * @package     Validation
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2019 - 2021 Jonathan Hulka
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
	 * 'help' (required): text telling user what is expected in case of failure. Handlers may override this.
	 * 'required' (optional default false): boolean indicates whether value is required to be present
	 * 'discard_empty' (optional default false): boolean indicates whether empty strings will be discarded from the result
	 * 'empty_as_null' (optional default false): boolean indicates whether empty strings will be converted to null (discard_empty overrides empty_as_null)
	 * 'trim_whitespace' (optional default true): boolean indicates whether whitespace will be trimmed
	 * custom handlers can be assigned to any of the filter types
	 * If 'required' and 'discard_empty' are both set, an empty string for that field will cause an error status
	 */
	protected
		$definitions,
		$handlers;
	/**
	 * flags can be specified in a filter definition array, or in its 'options' array, if it is in both, the outer version will override the 'options' version
	 * @param array $definitions as passed to filter_var_array with additional elements as described above
	 * @param array $customHandlers [(filter name) => (\ZedBoot\Validation\FilterHandlerInterface instance), ...]
	 */
	public function __construct(
		array $definitions,
		array $customHandlers = []
	)
	{
		$this->definitions = [];
		$this->handlers = $customHandlers;
		$badDefs = [];
		$errs = [];
		//Find any errors in handlers and definitions
		foreach($this->handlers as $k => $h)
		{
			if(!is_object($h) || !($h instanceof \ZedBoot\Validation\FilterHandlerInterface))
			{
				throw new \Exception('$customHandlers['.$k.']: expected \\ZedBoot\\Validation\\FilterHandlerInterface, got '.$this->getTypeString($h));
			}
		}
		foreach($definitions as $k => $def) $this->initDefinition($k, $def, $badDefs, $errs);
		if(!empty($errs)) throw new \Exception(implode(PHP_EOL, $errs));
	}

	protected function initDefinition($k, $def, array &$badDefs, array &$errs)
	{
		$ok = true;
		$filter = null;
		if($ok)
		{
			if(!is_array($def))
			{
				$ok = false;
				$badDefs[$k] = $k;
				$errs[] = '$definitions['.$k.']: expected array, got '.$this->getTypeString($def);
			}
			else foreach(['filter', 'name', 'help'] as $req) if(!array_key_exists($req, $def))
			{
				$ok = false;
				$badDefs[$k] = $k;
				$errs[] = 'Missing $definitions['.$k.']['.$req.']';
			}
		}
		if($ok)
		{
			$filter = $def['filter'];
			if(!is_scalar($filter))
			{
				$ok = false;
				$badDefs[$k] = $k;
				$errs[] = '$definitions['.$k.'][\'filter\']: expected scalar, got '.$this->getTypeString($filter).'.';
			}
		}
		if($ok)
		{
			if(array_key_exists($filter, $this->handlers))
			{
				unset($def['filter']);
				$def['filter_handler'] = $this->handlers[$filter];
			}
			foreach(['filter' => 'integer', 'name' => 'string', 'help' => 'string'] as $name => $type) if(array_key_exists($name, $def) && gettype($def[$name]) !== $type)
			{
				$badDefs[$k] = $k;
				$ok = false;
				$errs[] = '$definitions['.$k.'][\''.$name.'\']: expected '.$type.', got '.$this->getTypeString($def[$name]).'.';
			}
		}
		if($ok)
		{
			if(!array_key_exists('options', $def)) $def['options'] = [];
			if(!array_key_exists('flags', $def)) $def['flags'] = null;
			$def['required'] = !empty($def['required']);
			$def['empty_as_null'] = !empty($def['empty_as_null']);
			//Trim whitespace by default
			$def['trim_whitespace'] = !array_key_exists('trim_whitespace', $def) || !empty($def['trim_whitespace']);
			$this->definitions[$k] = $def;
		}
	}

	protected function getTypeString($v)
	{
		return is_scalar($v)
			? gettype($v).'('.json_encode($v).')'
			: (is_object($v)
				? get_class($v)
				: gettype($v));
	}

	public function validate(array $parameters) : array
	{
		$ok = true;
		$result = false;
		$messages = [];
		$vs = [];
		//Only apply filters to parameters that are present
		$toApply = [];
		foreach($this->definitions as $k => $def)
		{
			if(is_scalar($parameters[$k]))
			{
				if($def['trim_whitespace']) $parameters[$k] = trim($parameters[$k]);
				if(strlen($parameters[$k]) === 0 && !empty($def['discard_empty'])) unset($parameters[$k]);
			}
			if(!array_key_exists($k, $parameters))
			{
				if($def['required'])
				{
					$ok = false;
					$messages[$k] = $def['name'] . ' is required.';
				}
			}
			else
			{
				$toApply[$k] = $def;
			}
		}
		foreach($toApply as $k => $def)
		{
			$itemOK = true;
			$msg = null;
			$in = $parameters[$k];
			$out = null;
			$nullOnFail = false;
			if(!is_scalar($in) || strlen($in) > 0 || !$def['empty_as_null'])
			{
				if(array_key_exists('filter', $def))
				{
					if(is_scalar($in))
					{
						//Options and flags must be nested in the $options parameter
						$options = ['options' => $def['options']];
						//If flags are at the outer level, use those
						$flags = $def['flags'];
						if($flags === null && array_key_exists('flags', $options))
						{
							//If no flags at the outer level, use flags at inner level
							$flags = $options['flags'];
						}
						if($def['filter'] === \FILTER_VALIDATE_BOOLEAN)
						{
							if($flags === null) $flags = 0;
							$flags |= \FILTER_NULL_ON_FAILURE;
						}
						if($flags !== null)
						{
							if($flags & \FILTER_NULL_ON_FAILURE) $nullOnFail = true;
							$options['flags'] = $flags;
						}
						$out = filter_var($in, $def['filter'], $options);
					}
					else
					{
						$ok = false;
						$messages[$k] = 'Invalid ' . $k . ': expected scalar data.';
					}
				}
				else
				{
					//This one has a custom handler
					['status' => $status, 'value' => $out, 'message' => $msg] =
						$def['filter_handler']->applyFilter($in, $def['name'], $def['options'], $def['flags'])
						+ ['status' => 'unknown', 'value' => false, 'message' => $def['help']];
					switch($status)
					{
						case 'success':
							break;
						case 'error':
							$ok = false;
							break;
						default:
							$ok = false;
							$msg = 'System error: unknown status from ' . get_class($def['filter_handler']) . '.';
							break;
					}
				}
				if(($nullOnFail && $out === null) || (!$nullOnFail && $out === false))
				{
					$ok = false;
					$messages[$k] = $msg === null ? $def['help'] : $msg;
				}
			}
			//else input is empty string and empty_as_null is specified - $out is already null
			$vs[$k] = $out;
		}
		if($ok)
		{
			$result = ['status' => 'success', 'data' => $vs];
		}
		else $result = ['status' => 'error', 'messages' => $messages];
		return $result;
	}
}
