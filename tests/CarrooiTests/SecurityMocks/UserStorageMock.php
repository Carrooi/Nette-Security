<?php

namespace CarrooiTests\SecurityMocks;

use Nette;
use Nette\Object;
use Nette\Security\IIdentity;
use Nette\Security\IUserStorage;

/**
 *
 * @author David Kudera
 */
class UserStorageMock extends Object implements IUserStorage
{


	/** @var bool */
	private $authenticated = false;


	/**
	 * @param bool $state
	 * @return $this
	 */
	function setAuthenticated($state)
	{
		$this->authenticated = $state;
		return $this;
	}


	/**
	 * @return bool
	 */
	function isAuthenticated()
	{
		return $this->authenticated === true;
	}


	/**
	 * @param \Nette\Security\IIdentity $identity
	 */
	function setIdentity(IIdentity $identity = NULL)
	{

	}


	/**
	 * @return \Nette\Security\IIdentity
	 */
	function getIdentity()
	{

	}


	/**
	 * @param string|int|\DateTime $time
	 * @param int $flags
	 */
	function setExpiration($time, $flags = 0)
	{

	}


	/**
	 * @return int
	 */
	function getLogoutReason()
	{

	}

}
