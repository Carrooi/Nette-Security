<?php

namespace Carrooi\Security\Authorization;

use Nette\Reflection\Method;

/**
 *
 * @author David Kudera <kudera.d@gmail.com>
 */
trait TPresenterAuthorization
{


	/**
	 * @param mixed $element
	 */
	abstract public function checkRequirements($element);


	/**
	 * @return \Carrooi\Security\User\User
	 */
	abstract public function getUser();


	/**
	 * @param \Nette\Reflection\Method $element
	 * @return bool
	 */
	public function checkMethodRequirements(Method $element)
	{
		return $this->getUser()->isAllowed([$this, $element]);
	}


	/**
	 * @param string $name
	 */
	public function checkComponentRequirements($name)
	{
		$method = 'createComponent'. ucfirst($name);
		$rc = Method::from($this, $method);

		$this->checkRequirements($rc);
	}

}