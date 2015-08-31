<?php

namespace Carrooi\Security\Authorization;

use Carrooi\Security\AuthorizatorClassNotExistsException;
use Carrooi\Security\AuthorizatorInvalidTypeException;
use Carrooi\Security\InvalidArgumentException;
use Nette\DI\Container;
use Nette\Object;
use Nette\Reflection\ClassType;

/**
 *
 * @author David Kudera
 */
class ResourcesManager extends Object
{


	/** @var \Nette\DI\Container */
	private $container;

	/** @var array */
	private $targetResources = [];

	/** @var \Carrooi\Security\Authorization\IResourceAuthorizator[]|string[] */
	private $authorizators = [];


	/**
	 * @param \Nette\DI\Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}


	/**
	 * @param string $class
	 * @param string $name
	 * @return $this
	 */
	public function addTargetResource($class, $name)
	{
		if (!isset($this->targetResources[$class])) {
			$this->targetResources[$class] = [];
		}

		if (array_search($name, $this->targetResources[$class]) === false) {
			$this->targetResources[$class][] = $name;
		}

		return $this;
	}


	/**
	 * @param mixed $resource
	 * @return array
	 */
	public function findTargetResources($resource)
	{
		if (is_string($resource)) {
			return [$resource];
		}

		if (!is_object($resource)) {
			throw new InvalidArgumentException('Security resource target can be only string or an object, '. gettype($resource). ' given.');
		}

		$className = get_class($resource);

		if (!isset($this->targetResources[$className])) {
			$rc = ClassType::from($resource);

			foreach ($this->targetResources as $class => $names) {
				if (
					(class_exists($class) && $rc->isSubclassOf($class)) ||
					(interface_exists($class) && $rc->implementsInterface($class))
				) {
					$className = $rc->getName();

					foreach ($names as $name) {
						$this->addTargetResource($className, $name);
					}
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
	 * @param string $class
	 * @return $this
	 */
	public function registerAuthorizator($name, $class)
	{
		if (!class_exists($class)) {
			throw new AuthorizatorClassNotExistsException('Authorizator class '. $class. ' is not valid class.');
		}

		$this->authorizators[$name] = $class;

		return $this;
	}


	/**
	 * @param string $name
	 * @return \Carrooi\Security\Authorization\IResourceAuthorizator|null
	 */
	public function getAuthorizator($name)
	{
		if (isset($this->authorizators[$name])) {
			$authorizator = $this->authorizators[$name];
		}

		if (isset($this->authorizators['*'])) {
			$authorizator = $this->authorizators['*'];
		}

		if (!isset($authorizator)) {
			return null;
		}

		if (is_object($authorizator)) {
			return $authorizator;
		}

		if (is_string($authorizator)) {
			return $this->authorizators[$name] = $this->container->getByType($authorizator);
		}

		throw new AuthorizatorInvalidTypeException('Authorizator can be only object or string.');
	}

}
