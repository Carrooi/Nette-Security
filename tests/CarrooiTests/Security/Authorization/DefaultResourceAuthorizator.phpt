<?php

/**
 * Test: Carrooi\Security\Authorization\DefaultResourceAuthorizator
 *
 * @testCase CarrooiTests\Security\Authorization\DefaultResourceAuthorizatorTest
 * @author David Kudera
 */

namespace CarrooiTests\Security\Authorization;

use Carrooi\Security\Authorization\DefaultResourceAuthorizator;
use Carrooi\Security\User\User;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__. '/../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class DefaultResourceAuthorizatorTest extends TestCase
{


	/** @var \Carrooi\Security\User\User|\Mockery\Mock */
	private $user;

	/** @var \Carrooi\Security\Authorization\DefaultResourceAuthorizator */
	private $resourceAuthorizator;


	public function setUp()
	{
		$this->user = \Mockery::mock(User::class);

		$this->resourceAuthorizator = new DefaultResourceAuthorizator;
	}


	public function tearDown()
	{
		\Mockery::close();
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
		$this->user->shouldReceive('isLoggedIn')->once()->andReturn(true);

		$this->resourceAuthorizator->addAction('view', null, true);

		Assert::true($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_loggedIn_notAllowed()
	{
		$this->user->shouldReceive('isLoggedIn')->once()->andReturn(false);

		$this->resourceAuthorizator->addAction('view', null, true);

		Assert::false($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_notLoggedIn_allowed()
	{
		$this->user->shouldReceive('isLoggedIn')->once()->andReturn(false);

		$this->resourceAuthorizator->addAction('view', null, false);

		Assert::true($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_notLoggedIn_notAllowed()
	{
		$this->user->shouldReceive('isLoggedIn')->once()->andReturn(true);

		$this->resourceAuthorizator->addAction('view', null, false);

		Assert::false($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_inRole_allowed()
	{
		$this->user
			->shouldReceive('isInRole')->once()->with('normal')->andReturn(false)
			->shouldReceive('isInRole')->once()->with('admin')->andReturn(true);

		$this->resourceAuthorizator->addAction('view', null, null, ['normal', 'admin']);

		Assert::true($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_inRole_notAllowed()
	{
		$this->user->shouldReceive('isInRole')->twice()->andReturn(false);

		$this->resourceAuthorizator->addAction('view', null, null, ['normal', 'admin']);

		Assert::false($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_others_allowed()
	{
		$this->user->shouldReceive('isLoggedIn')->once()->andReturn(true);

		$this->resourceAuthorizator->addAction('*', null, true);

		Assert::true($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed_others_notAllowed()
	{
		$this->user->shouldReceive('isLoggedIn')->once()->andReturn(false);

		$this->resourceAuthorizator->addAction('*', null, true);

		Assert::false($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}


	public function testIsAllowed()
	{
		$this->user
			->shouldReceive('isLoggedIn')->once()->andReturn(true)->getMock()
			->shouldReceive('isInRole')->once()->with('normal')->andReturn(false)->getMock()
			->shouldReceive('isInRole')->once()->with('admin')->andReturn(true)->getMock();

		$this->resourceAuthorizator->addAction('view', null, true, ['normal', 'admin']);

		Assert::true($this->resourceAuthorizator->isAllowed($this->user, 'view'));
	}

}


(new DefaultResourceAuthorizatorTest)->run();
