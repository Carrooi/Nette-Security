<?php

namespace Carrooi\Security\Authorization;

use Carrooi\Security\User\User;
use Nette\Object;

/**
 *
 * @author David Kudera
 */
class Authorizator extends Object
{


	/** @var \Carrooi\Security\Authorization\ResourcesManager */
	private $resourcesManages;

	/** @var bool */
	private $default = false;


	/**
	 * @param \Carrooi\Security\Authorization\ResourcesManager $resourcesManager
	 */
	public function __construct(ResourcesManager $resourcesManager)
	{
		$this->resourcesManages = $resourcesManager;
	}


	/**
	 * @return \Carrooi\Security\Authorization\ResourcesManager
	 */
	public function getResourcesManager()
	{
		return $this->resourcesManages;
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
	 * @param \Carrooi\Security\User\User $user
	 * @param mixed $resource
	 * @param string $action
	 * @return bool
	 */
	function isAllowed(User $user, $resource, $action)
	{
		$name = $this->resourcesManages->getTargetResource($resource);
		if (!$name) {
			return $this->default;
		}

		$authorizator = $this->resourcesManages->getAuthorizator($name);
		if (!$authorizator) {
			return $this->default;
		}

		$resource = $resource === $name ? null : $resource;

		return $authorizator->isAllowed($user, $action, $resource);
	}

}
