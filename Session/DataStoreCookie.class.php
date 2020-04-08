<?php
/**
 * Class DataStoreCookie | ZedBoot/Session/DataStoreCookie.class.php
 * @license     GNU General Public License, version 3
 * @package     Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2019 - 2020 Jonathan Hulka
 */

/**
 * Implementation of CookieInterface using a single DataStore to hold the index.
 */
namespace ZedBoot\Session;
use \ZedBoot\Error\ZBError as Err;
class DataStoreCookie implements \ZedBoot\Session\CookieInterface
{
	protected static
		$gcMax=10; //number of indices to be cleaned up each time a cookie is created
	protected
		$indexDS,
		$name,
		$expireSeconds,
		$cookieStringLength,
		$internalIdStringLength,
		$id=null;

	public function __construct(
		\ZedBoot\DataStore\DataStoreInterface $dataStore,
		string $name,
		int $expireSeconds=300,
		int $cookieStringLength=128,
		int $internalIdStringLength=32)
	{
		$this->indexDS=$dataStore;
		$this->name=$name;
		$this->expireSeconds=$expireSeconds;
		$this->cookieStringLength=$cookieStringLength;
		$this->internalIdStringLength=$internalIdStringLength;
	}
	
	public function setClientId(string $id)
	{
		$_COOKIE[$this->name]=$id;
		$this->id=null;
	}
	
	public function getId(bool $create=true,bool $regenerate=false): ?string
	{
		if($regenerate) $this->id=null;
		if($this->id===null)
		{
			//Ensure that this won't happen on a non-secure connection
			if(!(
				(!empty($_SERVER['HTTPS']) && 'off' != $_SERVER['HTTPS']) ||
				(array_key_exists('SERVER_PORT', $_SERVER) && 443 === (int)$_SERVER['SERVER_PORT']) ||
				(array_key_exists('HTTP_X_FORWARDED_SSL', $_SERVER) && 'on' === $_SERVER['HTTP_X_FORWARDED_SSL']) ||
				(array_key_exists('HTTP_X_FORWARDED_PROTO', $_SERVER) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'])
			)) throw new Err('Session insecure: not using HTTPS.');
			$this->load($create,$regenerate);
		}
		return $this->id;
	}
	
	/**
	 * When using this, it is a good idea to clear or expire any session data at the same time.
	 */
	public function reset()
	{
		$cookie=null;
		$cookieId=null;
		//Ensure that this won't happen on a non-secure connection
		if(!(
			(!empty($_SERVER['HTTPS']) && 'off' != $_SERVER['HTTPS']) ||
			(array_key_exists('SERVER_PORT', $_SERVER) && 443 === (int)$_SERVER['SERVER_PORT']) ||
			(array_key_exists('HTTP_X_FORWARDED_SSL', $_SERVER) && 'on' === $_SERVER['HTTP_X_FORWARDED_SSL']) ||
			(array_key_exists('HTTP_X_FORWARDED_PROTO', $_SERVER) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'])
		)) throw new Err('Session insecure: not using HTTPS.');
		if(!empty($_COOKIE[$this->name]))
		{
			//Client has sent a cookie
			$cookieId=$_COOKIE[$this->name];
//Enter critical section
			$index=$this->prepIndex($this->indexDS->lockAndRead());
			if(array_key_exists($cookieId,$index['by_cookie']))
			{
				//Expire the cookie, but keep it in the index.
				//gc will take care of it, and for security reasons,
				//it should stay for a while.
				$index['by_cookie'][$cookieId]['expiry']=time()-1;
			}
//Exit critical section
			$this->indexDS->writeAndUnlock($index);
			$this->setCookie(null,-1);
		}
		$this->id=null;
	}
	
	protected function prepIndex($data)
	{
		if(!is_array($data)) $data=[];
		if(!array_key_exists('by_cookie',$data)) $data['by_cookie']=[];
		if(!array_key_exists('by_id',$data)) $data['by_id']=[];
		return $data;
	}

	protected function getRandom($length)
	{
		$result='';
		do
		{
			//Discard the last 3 bytes, because bit packing makes the last few characters less random
			$result.=substr
			(
				//Discard non-alhpanumeric characters
				str_replace
				(
					['/','+','='],'',
					base64_encode
					(
						//Base 64 produces 4 characters for each 3 bytes, so most times this will give enough bytes in a single pass
						random_bytes(($length-strlen($result))*0.8+4)
					)
				),
				0,
				-3
			);
		}while(strlen($result)<$length);
		//Prepend a character to ensure there is never a completely numeric result
		return 'c'.substr($result,0,$length);
	}
	
	protected function helpCreate(&$index)
	{
		$internalId=null;
		$cookieId=null;
		$this->gc($index);
		//Find a unique internal id
		do{ $internalId=$this->getRandom($this->internalIdStringLength); }while(array_key_exists($internalId,$index['by_id']));
		do{ $cookieId=$this->getRandom($this->cookieStringLength); }while(array_key_exists($cookieId,$index['by_cookie']));
		$index['by_id'][$internalId]=$cookieId;
		$index['by_cookie'][$cookieId]=['expiry'=>time()+$this->expireSeconds,'id'=>$internalId];
		$this->id=$internalId;
//		$this->setCookie($cookieId,time()+$this->expireSeconds);
		//Keep the cookie alive on the client side for one day
		//If most communication is being done via websocket, the client
		//may not be checking back via http very often
		$this->setCookie($cookieId,time()+60*60*24);
	}

	/**
	 * $this->id will be set to internal cookie id or null
	 */
	protected function load($create,$regenerate=false)
	{
		//Assuming this is first time loading or we are regenerating
		$cookieId=null;
		$cookie=null;
		$ds=null;
		$now=time();
		if(empty($_COOKIE[$this->name]))
		{
			if($create)
			{
//Enter critical section
				$index=$this->prepIndex($this->indexDS->lockAndRead());
				$this->helpCreate($index);
//Exit critical section
				$this->indexDS->writeAndUnlock($index);
			}
		}
		else
		{
			$cookieId=$_COOKIE[$this->name];
//Enter critical section
			$index=$this->prepIndex($this->indexDS->lockAndRead());
			if(!array_key_exists($cookieId,$index['by_cookie']) || $index['by_cookie'][$cookieId]['expiry']<$now)
			{
				//Cookie data not found or expired
				if($create) $this->helpCreate($index);
			}
			else
			{
				//Valid cookie has been found
				$byCookie=&$index['by_cookie'];
				$this->id=$byCookie[$cookieId]['id'];
				if($regenerate)
				{
					//We need a new cookie, but keep the old internal id
					$old=$cookieId;
					do{ $cookieId=$this->getRandom($this->cookieStringLength); }while(array_key_exists($cookieId,$byCookie));
					$byCookie[$cookieId]=$byCookie[$old];
					$index['by_id'][$this->id]=$cookieId;
					unset($byCookie[$old]);
				}
				$exp=time()+$this->expireSeconds;
				$byCookie[$cookieId]['expiry']=$exp;
				$this->setCookie($cookieId,$exp);
			}
			if($this->id!==null)
			{
				$this->indexDS->write($index);
			}
//Exit critical section
			$this->indexDS->unlock();
		}
	}

	protected function gc(&$index)
	{
		$now=time();
		$byCookie=&$index['by_cookie'];
		$byId=&$index['by_id'];
		$c=max(count($byCookie),static::$gcMax);
		//Cycle through the by_cookie index
		for($i=0;$i<$c;$i++)
		{
			reset($byCookie);
			$cookieId=key($byCookie);
			$params=array_shift($byCookie);
			//Keys are kept in the index for double the expiry period.
			//This is a safeguard; it ensures that they don't get reused
			//too quickly after expiry in case there is some residual
			//data. (it prevents data leaking into another session)
			if($params['expiry']+$this->expireSeconds<$now)
			{
				unset($byId[$params['id']]);
				unset($byCookie[$cookieId]);
			}
			else $byCookie[$cookieId]=$params;
		}
		//Cycle through the by_id index to ensure no orphan indices are left behind
		$c=max(count($byId),static::$gcMax);
		for($i=0;$i<$c;$i++)
		{
			reset($byId);
			$id=key($byId);
			$cookieId=array_shift($byId);
			if(array_key_exists($cookieId,$byCookie)) $byId[$id]=$cookieId;
		}
	}
	protected function setCookie($value,$expiry)
	{
		if(!setcookie(
			$this->name, //name
			$value,      //value
			$expiry,     //expiry
			'/',         //path
			getenv('HTTP_HOST'), //domain
			true,        //secure (https only)
			true         //http only (no js)
		)) throw new Err('Unable to set cookie. This must happen before output begins.');
		//In case anyone else is interested
		$_COOKIE[$this->name]=$value;
	}
}
