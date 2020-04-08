<?php
/**
 * Class SimpleURLRouter | ZedBoot/Bootstrap/SimpleURLRouter.class.php
 * @license     GNU General Public License, version 3
 * @package     Bootstrap
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

namespace ZedBoot\Bootstrap;
use \ZedBoot\Error\ZBError as Err;
class SimpleURLRouter implements \ZedBoot\Bootstrap\URLRouterInterface
{
	private static
		$debug=false;
	protected
		$routes=null,
		$routeData=null,
		$baseURL=null,
		$urlParameters=null,
		$urlParts=null,
		$roles=null;
	public function __construct(Array $routes){ $this->routes=$routes; }
	public function parseURL($url)
	{
		$routeKey=null;
		$data=null;
		$routeString=trim($url,'/');
		$routeKey=$this->parseRoute($routeString);
		if(!array_key_exists($routeKey,$this->routes)) throw new Err('Route not found.');
		$this->routeData=$this->routes[$routeKey];
	}
	public function getRouteData(): ?array{ return $this->routeData; }
	public function getBaseURL(): ?string{ return $this->baseURL; }
	public function getURLParameters(): ?array{ return $this->urlParameters; }
	public function getURLParts(): ?array{ return $this->urlParts; }
	private function parseRoute($routeString)
	{
		$result=null;
		$urlParts=null;
		$found=null;
		if(empty($routeString))
		{
			$urlParts=array();
		}
		else $urlParts=explode('/',$routeString);
		if(isset($this->routes[$routeString])) //The obvious easy answer
		{
			$result=$routeString;
			$this->urlParts=$urlParts;
			$this->urlParameters=array();
			$this->baseURL=$routeString;
		}
		else
		{
			//construct a search expression including wildcards
			$delim='';
			$searchString='';
			foreach($urlParts as $part)
			{
				//Each part can be matched exactly or by '*'
				$searchString.='('.$delim.'('.preg_quote($part,'/').'|\*)';
				$delim='\\/';
			}
			$searchString.=str_repeat(')?',count($urlParts));
			$searchString='/^'.$searchString.'$/';
			$matches=preg_grep($searchString,array_keys($this->routes));
			$exploded=array();
			foreach($matches as $match)$exploded[]=empty($match)?array():explode('/',$match);
			$this->urlParameters=$urlParts;
			$this->urlParts=array();
			//filterRoutes() will transfer parts from $this->urlParameters to $this->urlParts as it parses through
			$routeParts=$this->filterRoutes($exploded,$this->urlParameters,$this->urlParts);
			//If $this->filterRoutes failed, it will have thrown an exception.
			//$routeParts now has the route string match including wildcards - it is the key to our routes array
			//$urlParts now has the route string match as requested
			//$urlParameters has all the trailing unmatched url segments
			$result=implode('/',$routeParts);
			$this->baseURL=implode('/',$this->urlParts);
		}
		return $result;
	}
	

	/**
	 * Recursion is probably overkill for this function
	 * Its algorithm is fairly linear:
	 *  - find the best routes to this url segment
	 *  - remove all others
	 *  - advance one segment
	 *  - repeat until only one route remains
	 */
	private function filterRoutes($routes,&$remainingURLParts,&$usedURLParts)
	{
		$result=null;
		$searchString='';
		$delim='';
		if(count($routes)>1)
		{
			//Longest matched routes take priority
			$nonEmpty=array();
			foreach($routes as $item) if(count($item)>0) $nonEmpty[]=$item;
			if(count($nonEmpty)>0) $routes=$nonEmpty;
		}
		if(count($routes)>1)
		{
			//Exact matches take priority over wildcards - if there are any, toss all wildcards
			$nonWildcards=array();
			foreach($routes as $item) if(count($item)>0 && $item[0]!='*') $nonWildcards[]=$item;
			if(count($nonWildcards)>0) $routes=$nonWildcards;
		}
		$count=count($routes);
		if($count>1)
		{
			if(count($routes[0])==0)
			{
				//Priority rules would have weeded out empty routes if non-empty ones were available
				//All remaining subroutes are empty, which means all routes matched to this point are identical
				//... which should mean that there is only one route
				//so if this happens, something went wrong
				throw new Err('This should never happen, multiple identical routes');
			}
			else
			{
				//More than one result, try to narrow it down
				//Remove the first url part and search again
				$usedURLParts[]=array_shift($remainingURLParts);
				$suffixes=array();
				$prefix=null;
				foreach($routes as $item) if(count($item)>0)
				{
					//There is only one prefix - either exact match or '*'
					//priority rules would have weeded out '*' if there is an exact match
					$prefix=array_shift($item);
					$suffixes[]=$item;
				}
				$search=$this->filterRoutes($suffixes,$remainingURLParts,$usedURLParts);
				if($search!==false) $result=array_merge(array($prefix),$search); //Recursive search was successful, otherwise return value will be false
			}
		}
		else if($count==1) //End condition - just one found - this is it
		{
			$result=$routes[0];
			$c=count($result);
			for($i=0; $i<$c; $i++) $usedURLParts[]=array_shift($remainingURLParts);
		}
		else throw new Err('Route not found.'); //End condition - nothing found
		return $result;
	}
}
