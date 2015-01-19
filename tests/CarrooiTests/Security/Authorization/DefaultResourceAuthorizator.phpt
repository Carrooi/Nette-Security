<?php

/**
 * Test: Carrooi\Security\Authorization\DefaultResourceAuthorizator
 *
 * @testCase CarrooiTests\Security\Authorization\DefaultResourceAuthorizatorTest
 * @author David Kudera
 */

namespace CarrooiTests\Security\Authorization;

use Carrooi\Security\Authorization\Authorizator;
use Carrooi\Security\Authorization\DefaultResourceAuthorizator;
use Carrooi\Security\Authorization\ResourcesManager;
use CarrooiTests\SecurityMocks\UserMock;
use CarrooiTests\SecurityMocks\UserStorageMock;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__. '/../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class DefaultResourceAuthorizatorTest extends TestCase
{


	/** @var \CarrooiTests\SecurityMocks\UserMock */
	private $user;

	/** @var \Carrooi\Security\Authorization\DefaultResourceAuthorizator */
	private $resourceAuthorizator;


	public function setUp()
	{
		$this->user = new UserMock(new UserStorageMock, new Authorizator(new ResourcesManager));
		$this->resourceAuthorizator = new DefaultResourceAuthorizator;
	}


	public function testIsAllowed_unknownAction()
	{
		Assert::false($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_unknownAction_setDefault()
	{
		$this->resourceAuthorizator->setDefault(true);

		Assert::true($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_default()
	{
		$this->resourceAuthorizator->addAction('view');

		Assert::true($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_allowed()
	{
		$this->resourceAuthorizator->addAction('view', true);

		Assert::true($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_notAllowed()
	{
		$this->resourceAuthorizator->addAction('view', false);

		Assert::false($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_loggedIn_allowed()
	{
		$this->resourceAuthorizator->addAction('view', null, true);
		$this->user->getStorage()->setAuthenticated(true);

		Assert::true($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_loggedIn_notAllowed()
	{
		$this->resourceAuthorizator->addAction('view', null, true);

		Assert::false($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_notLoggedIn_allowed()
	{
		$this->resourceAuthorizator->addAction('view', null, false);

		Assert::true($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_notLoggedIn_notAllowed()
	{
		$this->resourceAuthorizator->addAction('view', null, false);
		$this->user->getStorage()->setAuthenticated(true);

		Assert::false($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_inRole_allowed()
	{
		$this->resourceAuthorizator->addAction('view', null, null, ['normal', 'admin']);
		$this->user->setRoles(['admin']);

		Assert::true($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_inRole_notAllowed()
	{
		$this->resourceAuthorizator->addAction('view', null, null, ['normal', 'admin']);

		Assert::false($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_others_allowed()
	{
		$this->resourceAuthorizator->addAction('*', null, true);
		$this->user->getStorage()->setAuthenticated(true);

		Assert::true($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_others_notAllowed()
	{
		$this->resourceAuthorizator->addAction('*', null, true);

		Assert::false($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed()
	{
		$this->resourceAuthorizator->addAction('view', null, true, ['normal', 'admin']);
		$this->user->getStorage()->setAuthenticated(true);
		$this->user->setRoles(['admin']);

		Assert::true($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}

}


run(new DefaultResourceAuthorizatorTest);
