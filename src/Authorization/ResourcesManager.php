<?php

namespace Carrooi\Security\Authorization;

use Nette\Object;
use Nette\Reflection\ClassType;

/**
 *
 * @author David Kudera
 */
class ResourcesManager extends Object
{


	/** @var array */
	private $targetResources = [];

	/** @var \Carrooi\Security\Authorization\IResourceAuthorizator[] */
	private $authorizators = [];


	/**
	 * @param string $class
	 * @param string $name
	 * @return $this
	 */
	public function addTargetResource($class, $name)
	{
		$this->targetResources[$class] = $name;
		return $this;
	}


	/**
	 * @param mixed $resource
	 * @return string
	 */
	public function getTargetResource($resource)
	{
		if (is_string($resource)) {
			return $resource;
		}

		$className = get_class($resource);

		if (!isset($this->targetResources[$className])) {
			$rc = ClassType::from($resource);

			foreach ($this->targetResources as $class => $name) {
				if ($rc->isSubclassOf($class)) {
					$this->targetResources[$rc->getName()] = $name;
					$className = $rc->getName();
					break;
				}
			}
		}

		if (isset($this->targetResources[$className])) {
			return $this->targetResources[$className];
		}

		return null;
	}


	/**
	 * @param string $name
	 * @param \Carrooi\Security\Authorization\IResourceAuthorizator $authorizator
	 * @return $this
	 */
	public function addAuthorizator($name, IResourceAuthorizator $authorizator)
	{
		$this->authorizators[$name] = $authorizator;
		return $this;
	}


	/**
	 * @param string $name
	 * @return \Carrooi\Security\Authorization\IResourceAuthorizator|null
	 */
	public function getAuthorizator($name)
	{
		if (isset($this->authorizators[$name])) {
			return $this->authorizators[$name];
		}

		if (isset($this->authorizators['*'])) {
			return $this->authorizators['*'];
		}

		return null;
	}

}
