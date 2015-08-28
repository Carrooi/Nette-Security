<?php

namespace CarrooiTests\SecurityMocks;

use Nette;
use Nette\Security\IIdentity;
use Nette\Security\IUserStorage;

/**
 *
 * @author David Kudera
 */
class UserStorageMock implements IUserStorage
{

	function setAuthenticated($state) {}

	function isAuthenticated() {}

	function setIdentity(IIdentity $identity = NULL) {}

	function getIdentity() {}

	function setExpiration($time, $flags = 0) {}

	function getLogoutReason() {}

}
