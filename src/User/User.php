<?php

namespace Carrooi\Security\User;

use Carrooi\Security\Authorization\Authorizator;
use Carrooi\Security\NotImplementedException;
use Nette\Security\IAuthorizator;
use Nette\Security\IUserStorage;
use Nette\Security\User as BaseUser;

/**
 *
 * @author David Kudera
 */
class User extends BaseUser
{



	/** @var \Carrooi\Security\Authorization\Authorizator */
	private $authorizator;


	/**
	 * @param \Nette\Security\IUserStorage $userStorage
	 * @param \Carrooi\Security\Authorization\Authorizator $authorizator
	 */
	public function __construct(IUserStorage $userStorage, Authorizator $authorizator)
	{
		parent::__construct($userStorage);

		$this->authorizator = $authorizator;
	}


	/**
	 * @param bool $need
	 * @return \Carrooi\Security\Authorization\Authorizator
	 */
	public function getAuthorizator($need = true)
	{
		return $this->authorizator;
	}


	/**
	 * @param \Nette\Security\IAuthorizator $authorizator
	 * @return \Nette\Security\User
	 */
	public function setAuthorizator(IAuthorizator $authorizator)
	{
		throw new NotImplementedException('Method '. __METHOD__. ' is not implemented.');
	}


	/**
	 * @param mixed $resource
	 * @param string $privilege
	 * @return bool
	 */
	public function isAllowed($resource = null, $privilege = null)
	{
		return $this->getAuthorizator()->isAllowed($this, $resource, $privilege);
	}

}
