<?php

namespace Carrooi\Security\Authorization;

use Carrooi\Security\InvalidStateException;
use Carrooi\Security\StrictModeException;
use Carrooi\Security\User\User;
use Nette\Application\UI\Presenter;
use Nette\Object;
use Nette\Reflection\ClassType;
use Nette\Reflection\Method;

/**
 *
 * @author David Kudera
 */
class Authorizator extends Object
{


	const MODE_OFF = 0;

	const MODE_ON = 1;

	const MODE_STRICT = 2;


	/** @var \Carrooi\Security\Authorization\ResourcesManager */
	private $resourcesManages;

	/** @var bool */
	private $default = false;

	/** @var int */
	private $actionsMode = self::MODE_ON;

	/** @var int */
	private $componentsMode = self::MODE_ON;

	/** @var int */
	private $signalsMode = self::MODE_ON;


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
	 * @return int
	 */
	public function getActionsMode()
	{
		return $this->actionsMode;
	}


	/**
	 * @param int $actionsMode
	 * @return $this
	 */
	public function setActionsMode($actionsMode)
	{
		$this->actionsMode = $actionsMode;
		return $this;
	}


	/**
	 * @return int
	 */
	public function getComponentsMode()
	{
		return $this->componentsMode;
	}


	/**
	 * @param int $componentsMode
	 * @return $this
	 */
	public function setComponentsMode($componentsMode)
	{
		$this->componentsMode = $componentsMode;
		return $this;
	}


	/**
	 * @return int
	 */
	public function getSignalsMode()
	{
		return $this->signalsMode;
	}


	/**
	 * @param int $signalsMode
	 * @return $this
	 */
	public function setSignalsMode($signalsMode)
	{
		$this->signalsMode = $signalsMode;
		return $this;
	}


	/**
	 * @param \Carrooi\Security\User\User $user
	 * @param mixed $resource
	 * @param string $action
	 * @return bool
	 */
	public function isAllowed(User $user, $resource, $action)
	{
		if (is_array($resource) && $resource[0] instanceof Presenter && $resource[1] instanceof Method) {
			$data = $this->parseMethod($resource[0], $resource[1]);

			/** @var \Nette\Application\UI\Presenter $presenter */

			$type = $data[0];
			$presenter = $data[1];
			$resource = $data[2];
			$action = $data[3];

			if (
				($type === 'signal' && $this->signalsMode === self::MODE_OFF) ||
				($type === 'component' && $this->componentsMode === self::MODE_OFF) ||
				($type === 'action' && $this->actionsMode === self::MODE_OFF) ||
				($type === 'action' && $this->actionsMode === self::MODE_ON && !$resource && !$action)
			) {
				return true;
			}

			if (($type === 'signal' || $type === 'component')) {
				if ($action === '*') {
					return true;

				} elseif (is_array($action) && in_array($presenter->getAction(), $action)) {
					return true;

				} else {
					return false;
				}
			}
		}

		$name = $this->resourcesManages->getTargetResource($resource);
		if (!$name) {
			return $this->default;
		}

		$authorizator = $this->resourcesManages->getAuthorizator($name);
		if (!$authorizator) {
			return $this->default;
		}

		$resource = $resource === $name ? null : $resource;

		$rc = ClassType::from($authorizator);
		$method = 'is'. ucfirst($action). 'Allowed';

		if ($rc->hasMethod($method) && $rc->getMethod($method)->isPublic()) {
			return (bool) $authorizator->{'is'. $action. 'Allowed'}($user, $resource);

		} else {
			return (bool) $authorizator->isAllowed($user, $action, $resource);
		}
	}


	/**
	 * @param \Nette\Application\UI\Presenter $presenter
	 * @param \Nette\Reflection\Method $method
	 * @return array
	 */
	private function parseMethod(Presenter $presenter, Method $method)
	{
		$name = $method->getName();

		if (preg_match('/^handle/', $name)) {
			$type = 'signal';

		} elseif (preg_match('/^createComponent/', $name)) {
			$type = 'component';

		} elseif (preg_match('/^action|render/', $name)) {
			$type = 'action';

		} else {
			throw new InvalidStateException;
		}

		$resource = $method->hasAnnotation('resource') ? $method->getAnnotation('resource') : null;
		$action = $method->hasAnnotation('action') ? $method->getAnnotation('action') : null;

		// check strict mode

		if ($type === 'action' && $this->actionsMode === self::MODE_STRICT && (!$resource || !$action)) {
			throw new StrictModeException(get_class($presenter). '::'. $name. '(): Missing resource or action annotation when security for actions is at strict mode.');
		}

		if ($type === 'component' && $this->componentsMode === self::MODE_STRICT && !$action) {
			throw new StrictModeException(get_class($presenter). '::'. $name. '(): Missing action annotation when security for components is at strict mode.');
		}

		if ($type === 'signal' && $this->signalsMode === self::MODE_STRICT && !$action) {
			throw new StrictModeException(get_class($presenter). '::'. $name. '(): Missing action annotation when security for signals is at strict mode.');
		}

		// object resource

		if ($type === 'action' && $resource && preg_match('/^::([a-zA-Z]+)\(\)$/', $resource, $match)) {
			$resource = $presenter->{$match[1]}();
		}

		// signals and components actions

		if ($type === 'signal' || $type === 'component') {
			if (!$action) {
				$action = '*';
			} elseif ($action !== '*') {
				$action = explode(', ', $action);
			}
		}

		return [$type, $presenter, $resource, $action];
	}

}
