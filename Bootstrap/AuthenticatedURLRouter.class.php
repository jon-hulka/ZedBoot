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
 * To specify any logged in user, use '*' for the role key
 * Subroute will be selected in the order specified
 * if no subroute is selected, any unused 'byRole' options will be stripped from the route data
 */
namespace ZedBoot\Bootstrap;
use \ZedBoot\Error\ZBError as Err;
class AuthenticatedURLRouter implements \ZedBoot\Bootstrap\URLRouterInterface
{
	protected
		$routeData=null,
		$error=null,
		$router=null,
		$loggedUser=null;
	public function __construct(\ZedBoot\Bootstrap\URLRouterInterface $router, \ZedBoot\Auth\LoggedUserInterface $loggedUser)
	{
		$this->router=$router;
		$this->loggedUser=$loggedUser;
	}
	
	public function getError(){ return $this->error; }
	
	public function parseURL($url)
	{
		$this->routeData=null;
		$this->router->parseURL($url);
		$routeData=$this->router->getRouteData();
		if(!is_array($routeData)) throw new \Err('Expected array in route data.');
		$this->routeData=$this->parseRouteData($routeData);
	}
	
	public function getBaseURL(){ return $this->router->getBaseURL(); }
	public function getURLParameters(){ return $this->router->getURLParameters(); }
	public function getURLParts(){ return $this->router->getURLParts(); }
	public function getRouteData(){ return $this->routeData; }
	
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
			unset($routeData['https']);
			unset($routeData['byRole']);
			$result=$routeData;
		}
		else $result=$subroute;
		return $result;
	}
	
	protected function parseRoles($byRole)
	{
		$result=null;
		$user=null;
		if(false===($user=$this->loggedUser->getUser())) throw new \Exception('System error: failed to retreived logged user.');
		if($user!==null && is_array($user['roles']))
		{
			$roles=$user['roles'];
			foreach($byRole as $k=>$subroute) if($k==='*' || false!==array_search($k,$roles))
			{
				if(!is_array($subroute)) throw new \Err('Expected array for \''.$k.'\' subroute.');
				$result=$subroute;
				break;
			}
		}
		return $result;
	}
}