<?php

namespace Carrooi\Security\DI;

use Carrooi\Security\Authorization\Authorizator;
use Nette\DI\CompilerExtension;
use Nette\DI\Config\Helpers;

/**
 *
 * @author David Kudera
 */
class SecurityExtension extends CompilerExtension
{


	/** @var array */
	private $defaults = [
		'default' => false,
		'targetResources' => [],
		'resources' => [],
		'components' => true,
		'signals' => true,
		'actions' => true,
	];

	/** @var array */
	private $resourceDefaults = [
		'default' => false,
		'actions' => [],
	];

	/** @var array */
	private $actionDefaults = [
		'allowed' => null,
		'loggedIn' => null,
		'roles' => [],
	];


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		foreach ($this->compiler->getExtensions('Carrooi\Security\DI\ITargetResourcesProvider') as $resourcesResolver) {
			/** @var \Carrooi\Security\DI\ITargetResourcesProvider $resourcesResolver */

			$config['targetResources'] = Helpers::merge($config['targetResources'], $resourcesResolver->getTargetResources());
		}

		$manager = $builder->addDefinition($this->prefix('resourcesManager'))
			->setClass('Carrooi\Security\Authorization\ResourcesManager');

		$builder->addDefinition($this->prefix('authorizator'))
			->setClass('Carrooi\Security\Authorization\Authorizator')
			->addSetup('setDefault', [$config['default']])
			->addSetup('setComponentsMode', [$this->parseMode($config['components'])])
			->addSetup('setSignalsMode', [$this->parseMode($config['signals'])])
			->addSetup('setActionsMode', [$this->parseMode($config['actions'])]);

		$builder->getDefinition('user')
			->setClass('Carrooi\Security\User\User');

		foreach ($config['targetResources'] as $class => $resource) {
			$manager->addSetup('addTargetResource', [$class, $resource]);
		}

		$count = 0;
		foreach ($config['resources'] as $resource => $data) {
			$resourceDefaults = $this->resourceDefaults;
			$resourceDefaults['default'] = $config['default'];

			if (is_string($data)) {
				$manager->addSetup('$service->addAuthorizator(?, $this->getByType(?))', [$resource, $data]);

			} elseif (is_array($data)) {
				$data = Helpers::merge($data, $resourceDefaults);

				$authorizatorName = $this->prefix('authorizator.'. $count);
				$authorizator = $builder->addDefinition($authorizatorName)
					->setClass('Carrooi\Security\Authorization\DefaultResourceAuthorizator')
					->addSetup('setDefault', [$data['default']])
					->setAutowired(false);

				foreach ($data['actions'] as $action => $settings) {
					if (is_bool($settings)) {
						$settings = ['allowed' => $settings];
					}

					$settings = Helpers::merge($settings, $this->actionDefaults);

					$authorizator->addSetup('addAction', [
						$action,
						$settings['allowed'],
						$settings['loggedIn'],
						$settings['roles'],
					]);
				}

				$manager->addSetup('$service->addAuthorizator(?, $this->getService(?))', [$resource, $authorizatorName]);

				$count++;
			}
		}
	}


	/**
	 * @param string|bool $mode
	 * @return int
	 */
	private function parseMode($mode)
	{
		if ($mode === 'strict') {
			return Authorizator::MODE_STRICT;
		} elseif ($mode) {
			return Authorizator::MODE_ON;
		} else {
			return Authorizator::MODE_OFF;
		}
	}

}
