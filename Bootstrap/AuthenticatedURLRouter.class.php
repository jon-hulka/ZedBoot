<?php
/**
 * Class AuthenticatedURLRouter | ZedBoot/Bootstrap/AuthenticatedURLRouter.class.php
 * @license     GNU General Public License, version 3
 * @package     Bootstrap
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 * 
 * Decorates a url router with authentication support
 * if route data contains 'byRole' => array(<role>=>array(...), ...) and a user is logged in with one of the specified roles, the first appropriate subroute will be selected
 * Subroute will be selected in the order specified
 * if no subroute is selected, any unused 'byRole' options will be stripped from the route data
 */
namespace ZedBoot\Bootstrap;
use \ZedBoot\Error\ZBError as Err;
class AuthenticatedURLRouter implements \ZedBoot\Bootstrap\URLRouterInterface
{
	protected
		$routeData=null,
		$router=null,
		$loggedUser=null;
	public function __construct(\ZedBoot\Bootstrap\URLRouterInterface $router, \ZedBoot\Auth\LoggedUserInterface $loggedUser)
	{
		$this->router=$router;
		$this->loggedUser=$loggedUser;
	}
	
	public function parseURL($url)
	{
		$this->routeData=null;
		$this->router->parseURL($url);
		$routeData=$this->router->getRouteData();
		if(!is_array($routeData)) throw new \Err('Expected array in route data.');
		$this->routeData=$this->parseRouteData($routeData);
	}
	
	public function getBaseURL(): ?string{ return $this->router->getBaseURL(); }
	public function getURLParameters(): ?array{ return $this->router->getURLParameters(); }
	public function getURLParts(): ?array{ return $this->router->getURLParts(); }
	public function getRouteData(): ?array{ return $this->routeData; }
	
	protected function parseRouteData($routeData)
	{
		$result=null;
		$subroute=null;
		if(array_key_exists('byRole',$routeData))
		{
			$byRole=$routeData['byRole'];
			if(!is_array($byRole)) throw new \Err('Expected array for \'byRole\' subroutes.');
			$subroute=$this->parseRoles($byRole);
		}
		if($subroute===null)
		{
			//End condition - no subroute to parse
			unset($routeData['byRole']);
			$result=$routeData;
		}
		else $result=$subroute;
		return $result;
	}
	
	protected function parseRoles($byRole)
	{
		$result=null;
		$user=$this->loggedUser->getUser();
		if($user)
		{
			$roles=array_key_exists('roles',$user)?is_array($user['roles'])?$user['roles']:[]:[];
			foreach($byRole as $k=>$subroute) if(false!==array_search($k,$roles))
			{
				if(!is_array($subroute)) throw new \Err('Expected array for \''.$k.'\' subroute.');
				$result=$subroute;
				break;
			}
		}
		return $result;
	}
}
