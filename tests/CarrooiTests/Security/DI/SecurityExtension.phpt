<?php

/**
 * Test: Carrooi\Security\Authorization\DI\SecurityExtension
 *
 * @testCase CarrooiTests\Security\DI\SecurityExtensionTest
 * @author David Kudera
 */

namespace CarrooiTests\Security\Authorization;

use CarrooiTests\Security\Model\Book;
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
	private $context;

	/** @var \CarrooiTests\SecurityMocks\UserMock */
	private $user;


	public function setUp()
	{
		$config = new Configurator;
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['appDir' => __DIR__. '/../']);
		$config->addConfig(__DIR__. '/../config/config.neon');

		$this->context = $config->createContainer();

		$this->user = $this->context->getByType('CarrooiTests\SecurityMocks\UserMock');
	}


	public function testDefaultResourceAuthorizator()
	{
		$authorizator = $this->user->getAuthorizator()->getResourcesManager()->getAuthorizator('user');

		Assert::type('Carrooi\Security\Authorization\DefaultResourceAuthorizator', $authorizator);
	}


	public function testCustomResourceAuthorizator()
	{
		$authorizator = $this->user->getAuthorizator()->getResourcesManager()->getAuthorizator('book');

		Assert::type('CarrooiTests\Security\Model\Books', $authorizator);
	}


	public function testGetDefault()
	{
		Assert::true($this->user->getAuthorizator()->getDefault());
	}


	public function testGetDefault_defaultAuthorizator()
	{
		$authorizator = $this->user->getAuthorizator()->getResourcesManager()->getAuthorizator('user');
		/** @var \Carrooi\Security\Authorization\DefaultResourceAuthorizator $authorizator */

		Assert::false($authorizator->getDefault());
	}


	public function testIsAllowed_defaultAuthorizator()
	{
		Assert::false($this->user->isAllowed('user', 'detail'));
		Assert::false($this->user->isAllowed('user', 'add'));
		Assert::false($this->user->isAllowed('user', 'delete'));

		Assert::true($this->user->isAllowed('user', 'view'));

		$this->user->getStorage()->setAuthenticated(true);

		Assert::true($this->user->isAllowed('user', 'detail'));

		Assert::false($this->user->isAllowed('user', 'add'));

		$this->user->setRoles(['normal', 'admin']);

		Assert::true($this->user->isAllowed('user', 'add'));

		Assert::false($this->user->isAllowed('user', 'edit'));
	}


	public function testIsAllowed_customAuthorizator()
	{

		Assert::true($this->user->isAllowed('book', 'view'));

		Assert::false($this->user->isAllowed('book', 'delete'));
		Assert::false($this->user->isAllowed('book', 'edit'));

		$this->user->getStorage()->setAuthenticated(true);
		$this->user->setRoles(['normal', 'admin', 'writer']);

		$book = new Book(5);

		Assert::false($this->user->isAllowed($book, 'edit'));

		$this->user->setId(5);

		Assert::true($this->user->isAllowed($book, 'edit'));
	}

}


run(new SecurityExtensionTest);
