<?php
namespace ZedBoot\System\Bootstrap;
/**
 * Class AutoLoader | ZedBoot/System/Bootstrap/AutoLoader.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Bootstrap
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016, Jonathan Hulka
 */

/**
 * Simple class loader
 * Namespaces are mapped to directories. Class files are expected to have a .class.php extension.
 */
class AutoLoader
{
	protected
		//Nested array of namespaces and paths - the 'path' element is optional at any level
		//array('NS'=>array('subs'=>array('NSOTHER'=>array('subs'=>array(...),'path'=>'/another_path/),'NSTHAT'=>array(...)),'path'='/path'))
		$namespaces=array();
	public function __construct()
	{
		$loader=$this;
		spl_autoload_register(function($className) use (&$loader)
		{
			$path=$loader->getPath($className);
			//Non-empty $path indicates that the namespace was matched
			//Load the file from the global namespace
			if(!empty($path)) include($path);
		});
	}
	/**
	 * Maps a namespace to a directory
	 * Sub-namespaces will be loaded as subdirectories, unless explicitly mapped.
	 * @param string $namespace leading and trailing backslashes are unnecessary
	 * @param string $path must be the absolute path
	 * @return void
	 */
	public function register($namespace,$path)
	{
		if(!empty($namespace))
		{
			$index=&$this->namespaces;
			$parts=explode('\\',trim($namespace,'\\'));
			$partIndex=null;
			while(!is_null($part=array_shift($parts)))
			{
				if(!array_key_exists($part,$index)) $index[$part]=array('subs'=>array());
				$partIndex=&$index[$part];
				$index=&$index[$part]['subs'];
			}
			if(is_array($partIndex)) $partIndex['path']=$path;
		}
	}
	/**
	 * Maps a class name to file path
	 * @param string $className
	 * @return string path of the class file
	 */
	public function getPath($className)
	{
		$result=null;
		$parts=explode('\\',trim($className,'\\'));
		if(count($parts)>1)
		{
			//Last item is the class name - don't process it
			$className=array_pop($parts);
			//$parts is modified by findPath
			$result=$this->findPath($parts,$this->namespaces);
			if($result!==null)
			{
				//The remaining elements of $parts are subdirectories of the namespace top level directory
				//Put the class name back
				$parts[]=$className;
				$result.=DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $parts).'.class.php';
			}
		}
		return $result;
	}
	
	/**
	 * Helper function for getPath
	 * Recursively searches and matches a namespace with its path. getPath() does pre and post processing
	 * @param array $parts namespace segments, not including path. After a namespace is found, only its sub-namespaces will be left in this parameter.
	 * @param array $index namespace index - see $this->namespaces for structure
	 * @param string|null $path path at current level of recursion
	 * @return string|null result path or null if none found
	 */
	protected function findPath(Array &$parts,Array $index,$path=null)
	{
		$result=null;
		if(count($parts)>0)
		{
			//Try the next level in
			$part=array_shift($parts);
			if(array_key_exists($part,$index)) $result=$this->findPath($parts,$index[$part]['subs'],empty($index[$part]['path'])?null:$index[$part]['path']);
			if(is_null($result))
				//Not found - rewind to next level out
				array_unshift($parts,$part);
		}
		if(is_null($result) && !empty($path))
			//End condition - no deeper level found and there is a path at this level
			$result=$path;
		return $result;
	}
}
