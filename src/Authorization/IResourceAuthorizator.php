<?php

namespace Carrooi\Security\Authorization;

use Carrooi\Security\User\User;

/**
 *
 * @author David Kudera
 */
interface IResourceAuthorizator
{


	/**
	 * @return array|string
	 */
	public function getActions();


	/**
	 * @param \Carrooi\Security\User\User $user
	 * @param string $action
	 * @param mixed $data
	 * @return bool
	 */
	public function isAllowed(User $user, $action, $data = null);

}
