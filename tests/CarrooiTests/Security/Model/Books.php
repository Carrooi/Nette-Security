<?php

namespace CarrooiTests\Security\Model;

use Carrooi\Security\Authorization\IResourceAuthorizator;
use Carrooi\Security\User\User;
use Nette\Object;

/**
 *
 * @author David Kudera
 */
class Books extends Object implements IResourceAuthorizator
{


	/**
	 * @param \Carrooi\Security\User\User $user
	 * @param string $action
	 * @param \CarrooiTests\Security\Model\Book $data
	 * @return bool
	 */
	public function isAllowed(User $user, $action, $data = null)
	{
		if ($action === 'view') {
			return true;

		} elseif ($action === 'edit') {
			return $user->isLoggedIn() && $user->isInRole('writer') && $data instanceof Book && $data->userId === $user->getId();

		}

		return false;
	}

}
