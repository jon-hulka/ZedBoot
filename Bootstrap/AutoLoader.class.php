<?php
namespace ZedBoot\Bootstrap;
/**
 * Class AutoLoader | ZedBoot/Bootstrap/AutoLoader.class.php
 * @license     GNU General Public License, version 3
 * @package     Bootstrap
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016, Jonathan Hulka
 */

/**
 * Simple class loader
 * Namespaces are mapped to directories.
 */
class AutoLoader
{
	protected
		//Set up by findPath
		$suffix,
		//Set up by findPath
		$prefix,
		//Nested array of namespaces and paths - the 'path' element indicates that that level is mapped
		//array(
		//	'NS'=>array( // \NS is not mapped to any path, it is here because sub-namespaces are mapped
		//		'subs'=>array(
		//			'NSOTHER'=>array( // \NS\NSOTHER is mapped to /another_path
		//				'subs'=>array(...),
		//				'path'=>'/another_path',
		//				'suffix'=>'.class.php', //default prefix and suffix for class file names
		//				'prefix'=>''
		//			),
		//			'NSTHAT'=>array(...) //And so on...
		//		)
		//	)
		//)
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
	 * @param string $suffix part of file name following the class name (normally the extension '.class.php')
	 * @param string $prefix part of file name preceding the class name (normally empty)
	 * @return void
	 */
	public function register($namespace,$path,$suffix='.class.php',$prefix='')
	{
		if(!empty($namespace))
		{
			$index=&$this->namespaces;
			//Break the namespace into segments
			$parts=explode('\\',trim($namespace,'\\'));
			$partIndex=null;
			while(!is_null($part=array_shift($parts)))
			{
				if(!array_key_exists($part,$index)) $index[$part]=array('subs'=>array());
				$partIndex=&$index[$part];
				$index=&$index[$part]['subs'];
			}
			if(is_array($partIndex))
			{
				$partIndex['path']=$path;
				$partIndex['suffix']=$suffix;
				$partIndex['prefix']=$prefix;
			}
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
				$parts[]=$this->prefix.$className.$this->suffix;
				$result.=DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $parts);
			}
		}
		return $result;
	}
	
	/**
	 * Helper function for getPath
	 * Recursively searches and matches a namespace with its path. getPath() does pre and post processing
	 * $this->suffix and $this->prefix will be modified on successful search
	 * @param array $parts namespace segments, not including path. After a namespace is found, only its sub-namespaces will be left.
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
			if(array_key_exists($part,$index))
			{
				$p=null;
				if(array_key_exists('path',$index[$part]))
				{
					//Presence of 'path' attribute indicates namespace registered at this level
					$p=$index[$part]['path'];
					//Get prefix and suffix out to top level of recursion
					$this->suffix=$index[$part]['suffix'];
					$this->prefix=$index[$part]['prefix'];
				}
				$result=$this->findPath($parts,$index[$part]['subs'],$p);
			}
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
