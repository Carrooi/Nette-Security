<?php

namespace Carrooi\Security\Authorization;

use Nette\Application\UI\MethodReflection;

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
	 * @param \Nette\Application\UI\MethodReflection $element
	 * @return bool
	 */
	public function checkMethodRequirements(MethodReflection $element)
	{
		return $this->getUser()->isAllowed([$this, $element]);
	}


	/**
	 * @param string $name
	 */
	public function checkComponentRequirements($name)
	{
		$method = 'createComponent'. ucfirst($name);
		$rc = new MethodReflection($this, $method);

		$this->checkRequirements($rc);
	}

}