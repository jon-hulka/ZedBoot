<?php
/**
 * Class AuthenticatedURLRouter | ZedBoot/System/Bootstrap/AuthenticatedURLRouter.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Bootstrap
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 * 
 * Decorates a url router with authentication support and https checking
 * if route data contains 'https' => array(...) and the request was sent by https, the subroute will be selected
 * if route data contains 'byRole' => array(<role>=>array(...), ...) and a user is logged in with one of the specified roles, the first appropriate subroute will be selected
 * 'https' and 'byRole' options can be nested.
 * if no subroute is selected, any unused 'https' or 'byRole' options will be stripped from the route data
 */
namespace \ZedBoot\System\Bootstrap;
class AuthenticatedURLRouter implements \ZedBoot\System\Bootstrap\URLRouterInterface
{
	protected
		$routeData=null,
		$error=null,
		$router=null,
		$loggedUser=null;
	public function __construct(\ZedBoot\System\Bootstrap\URLRouterInterface $router, \ZedBoot\System\Auth\LoggedUserInterface $loggedUser)
	{
		$this->router=$router;
		$this->loggedUser=$loggedUser;
	}
	
	public function getError(){ return $this->error; }
	
	public function parseURL($url)
	{
		$this->routeData=null;
		try
		{
			if(!$this->router->parseURL($url)) throw new \Exception('System error: route not found.');
			$routeData=$this->router->getRouteData();
			if(!is_array($routeData)) throw new \Exception('System error: expected array in route data.');
			$this->routeData=$this->parseRouteData($routeData);
		}
		catch(\Exception $e)
		{
			error_log($e);
			$this->error=$e->getMessage();
		}
	}
	
	public function getBaseURL()
	{
		$result=$this->router->getBaseURL();
		if($result===false) $this->error=$this->router->getError();
	}
	public function getURLParameters()
	{
		$result=$this->router->getURLParameters();
		if($result===false) $this->error=$this->router->getError();
	}
	public function getURLParts(){ return $this->router->getURLParts(); }
	{
		$result=$this->router->getURLParameters();
		if($result===false) $this->error=$this->router->getError();
	}
	public function getRouteData(){ return $this->routeData; }
	
	public function parseRouteData($routeData)
	{
		$result=false;
		$subroute=null;
		if(array_key_exists('https',$routeData) && 
			(
				(array_key_exists("HTTPS", $_SERVER) && 'on' === $_SERVER["HTTPS"]) ||
				(array_key_exists("SERVER_PORT", $_SERVER) && 443 === (int)$_SERVER["SERVER_PORT"]) ||
				(array_key_exists("HTTP_X_FORWARDED_SSL", $_SERVER) && 'on' === $_SERVER["HTTP_X_FORWARDED_SSL"]) ||
				(array_key_exists("HTTP_X_FORWARDED_PROTO", $_SERVER) && 'https' === $_SERVER["HTTP_X_FORWARDED_PROTO"])
			)
		{
			$subroute=$routeData['https'];
			if(!is_array($subroute)) throw new \Exception('System error: expected array for \'https\' subroute.');
		}
		else if(array_key_exists('byRole',$routeData))
		{
			$byRole=$routeData['byRole'];
			if(!is_array($byRole)) throw new \Exception('System error: expected array for \'byRole\' subroutes.');
			$subroute=parseRoles($byRole);
		}
		if($subroute===null)
		{
			//End condition - no subroutes to parse
			unset($routeData['https']);
			unset($routeData['byRole']);
			$result=$routeData;
		}
		else $result=parseRouteData($subroute);
		return $result;
	}
	
	protected function parseRoles($byRole)
	{
		$result=null;
		$user=null;
		if(false===($user=$this->loggedUser->getUser()) throw new \Exception($this->loggedUser->getError());
		if($user!==null && is_array($user['roles']))
		{
			$roles=$user['roles'];
			foreach($byRole as $k=>$subroute) if(false!==array_search($k,$roles))
			{
				if(!is_array($subroute)) throw new \Exception('System error: expected array for \''.$k.'\' subroute.');
				$result=$subroute;
				break;
			}
		}
		return $result;
	}
}