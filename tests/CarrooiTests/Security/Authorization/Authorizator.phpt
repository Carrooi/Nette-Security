<?php

/**
 * Test: Carrooi\Security\Authorization\Authorizator
 *
 * @testCase CarrooiTests\Security\AuthorizatorTest
 * @author David Kudera
 */

namespace CarrooiTests\Security\Authorization;

use Carrooi\Security\Authorization\Authorizator;
use Carrooi\Security\Authorization\DefaultResourceAuthorizator;
use Carrooi\Security\Authorization\ResourcesManager;
use CarrooiTests\Security\Model\Book;
use CarrooiTests\Security\Model\Books;
use CarrooiTests\SecurityMocks\UserMock;
use CarrooiTests\SecurityMocks\UserStorageMock;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__. '/../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class AuthorizatorTest extends TestCase
{


	/** @var \Carrooi\Security\Authorization\ResourcesManager */
	private $manager;

	/** @var \Carrooi\Security\Authorization\Authorizator */
	private $authorizator;

	/** @var \CarrooiTests\SecurityMocks\UserMock */
	private $user;


	public function setUp()
	{
		$this->manager = new ResourcesManager;
		$this->authorizator = new Authorizator($this->manager);
		$this->user = new UserMock(new UserStorageMock, $this->authorizator);
	}


	public function testIsAllowed_unknownResource()
	{
		Assert::false($this->authorizator->isAllowed($this->user, 'user', 'view'));
	}


	public function testIsAllowed_unknownResource_setDefault()
	{
		$this->authorizator->setDefault(true);

		Assert::true($this->authorizator->isAllowed($this->user, 'user', 'view'));
	}


	public function testIsAllowed_defaultResource()
	{
		$resourceAuthorizator = new DefaultResourceAuthorizator;
		$resourceAuthorizator
			->addAction('add', null, true, ['normal', 'admin']);

		$this->manager->addAuthorizator('user', $resourceAuthorizator);

		$this->user->getStorage()->setAuthenticated(true);
		$this->user->setRoles(['admin']);

		Assert::false($this->authorizator->isAllowed($this->user, 'user', 'view'));
		Assert::true($this->authorizator->isAllowed($this->user, 'user', 'add'));
	}


	public function testIsAllowed_targetResource_unknown()
	{
		Assert::false($this->authorizator->isAllowed($this->user, new Book, 'view'));
	}


	public function testIsAllowed_targetResource()
	{
		$this->manager->addTargetResource('CarrooiTests\Security\Model\Book', 'book');
		$this->manager->addAuthorizator('book', new Books);

		$this->user->setId(5);
		$this->user->setRoles(['writer']);
		$this->user->getStorage()->setAuthenticated(true);

		Assert::true($this->authorizator->isAllowed($this->user, 'book', 'view'));
		Assert::true($this->authorizator->isAllowed($this->user, new Book(5), 'edit'));

		$this->user->getStorage()->setAuthenticated(false);

		Assert::false($this->authorizator->isAllowed($this->user, new Book(5), 'edit'));
	}

}


run(new AuthorizatorTest);
