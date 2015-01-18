<?php

namespace CarrooiTests\SecurityMocks;

use Carrooi\Security\User\User as BaseUser;

/**
 *
 * @author David Kudera
 */
class UserMock extends BaseUser
{


	/** @var array */
	private $roles = [];

	/** @var int */
	private $id;


	/**
	 * @return array
	 */
	public function getRoles()
	{
		return $this->roles;
	}


	/**
	 * @param array $roles
	 * @return $this
	 */
	public function setRoles(array $roles)
	{
		$this->roles = $roles;
		return $this;
	}


	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}


	/**
	 * @param int $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

}
