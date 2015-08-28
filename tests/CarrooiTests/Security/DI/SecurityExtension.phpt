<?php

/**
 * Test: Carrooi\Security\Authorization\DI\SecurityExtension
 *
 * @testCase CarrooiTests\Security\DI\SecurityExtensionTest
 * @author David Kudera
 */

namespace CarrooiTests\Security\Authorization;

use Carrooi\Security\Authorization\IResourceAuthorizator;
use Carrooi\Security\User\User;
use Nette\Configurator;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class SecurityExtensionTest extends TestCase
{


	/** @var \Nette\DI\Container */
	private $container;

	/** @var \Carrooi\Security\User\User */
	private $user;

	/** @var \Carrooi\Security\Authorization\Authorizator */
	private $authorizator;


	public function setUp()
	{
		$config = new Configurator;
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['appDir' => __DIR__. '/../']);
		$config->addConfig(__DIR__. '/../config/config.neon');

		$this->container = $config->createContainer();

		$this->user = $this->container->getByType('Carrooi\Security\User\User');
		$this->authorizator = $this->container->getByType('Carrooi\Security\Authorization\Authorizator');
	}


	public function testDefaultResourceAuthorizator()
	{
		$authorizator = $this->authorizator->getResourcesManager()->getAuthorizator('user');

		Assert::type('Carrooi\Security\Authorization\DefaultResourceAuthorizator', $authorizator);
	}


	public function testCustomResourceAuthorizator()
	{
		$authorizator = $this->authorizator->getResourcesManager()->getAuthorizator('book');

		Assert::type('CarrooiTests\Security\Authorization\Books', $authorizator);
	}


	public function testGetDefault()
	{
		Assert::true($this->authorizator->getDefault());
	}


	public function testGetDefault_defaultAuthorizator()
	{
		$authorizator = $this->authorizator->getResourcesManager()->getAuthorizator('user');
		/** @var \Carrooi\Security\Authorization\DefaultResourceAuthorizator $authorizator */

		Assert::false($authorizator->getDefault());
	}

}


/**
 * @author David Kudera
 */
class Books implements IResourceAuthorizator
{


	/**
	 * @param \Carrooi\Security\User\User $user
	 * @param string $action
	 * @param mixed $data
	 * @return bool
	 */
	public function isAllowed(User $user, $action, $data = null)
	{

	}

}


run(new SecurityExtensionTest);
