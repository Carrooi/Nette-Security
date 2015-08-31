<?php

namespace Carrooi\Security\Authorization;

use Carrooi\Security\User\User;
use Nette\Object;

/**
 *
 * @author David Kudera
 */
class DefaultResourceAuthorizator extends Object implements IResourceAuthorizator
{


	/** @var array */
	private $actions = [];

	/** @var bool */
	private $default = false;


	/**
	 * @param string $action
	 * @param bool $allowed
	 * @param bool $loggedIn
	 * @param array $roles
	 * @return $this
	 */
	public function addAction($action, $allowed = null, $loggedIn = null, array $roles = [])
	{
		$this->actions[$action] = [
			'allowed' => $allowed,
			'loggedIn' => $loggedIn,
			'roles' => $roles,
		];

		return $this;
	}


	/**
	 * @return bool
	 */
	public function getDefault()
	{
		return $this->default;
	}


	/**
	 * @param bool $default
	 * @return $this
	 */
	public function setDefault($default)
	{
		$this->default = $default;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getActions()
	{
		return '*';
	}


	/**
	 * @param \Carrooi\Security\User\User $user
	 * @param string $action
	 * @param mixed $data
	 * @return bool
	 */
	public function isAllowed(User $user, $action, $data = null)
	{
		$a = null;

		if (isset($this->actions[$action])) {
			$a = $this->actions[$action];
		} else {
			if (!isset($this->actions['*'])) {
				return $this->default;
			}

			$a = $this->actions['*'];
		}

		if ($a['allowed'] !== null) {
			return $a['allowed'] === true;
		}

		if ($a['loggedIn'] !== null) {
			if ($a['loggedIn'] !== $user->isLoggedIn()) {
				return false;
			}
		}

		if (!empty($a['roles'])) {
			$ok = false;
			foreach ($a['roles'] as $role) {
				if ($user->isInRole($role)) {
					$ok = true;
					break;
				}
			}

			if (!$ok) {
				return false;
			}
		}

		return true;
	}

}
