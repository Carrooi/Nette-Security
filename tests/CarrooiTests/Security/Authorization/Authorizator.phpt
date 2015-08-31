<?php

/**
 * Test: Carrooi\Security\Authorization\Authorizator
 *
 * @testCase CarrooiTests\Security\AuthorizatorTest
 * @author David Kudera
 */

namespace CarrooiTests\Security\Authorization;

use Carrooi\Security\Authorization\Authorizator;
use Carrooi\Security\Authorization\IResourceAuthorizator;
use Carrooi\Security\User\User;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__. '/../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class AuthorizatorTest extends TestCase
{


	/** @var \Carrooi\Security\Authorization\ResourcesManager|\Mockery\MockInterface */
	private $manager;

	/** @var \Carrooi\Security\User\User|\Mockery\MockInterface */
	private $user;

	/** @var \Carrooi\Security\Authorization\Authorizator */
	private $authorizator;


	public function setUp()
	{
		$this->manager = \Mockery::mock('Carrooi\Security\Authorization\ResourcesManager');
		$this->user = \Mockery::mock('Carrooi\Security\User\User');

		$this->authorizator = new Authorizator($this->manager);
	}


	public function tearDown()
	{
		\Mockery::close();
	}


	public function testIsAllowed_unknownResource()
	{
		$this->manager->shouldReceive('findTargetResources')->once()->with('user')->andReturnNull()->getMock();

		Assert::false($this->authorizator->isAllowed($this->user, 'user', 'view'));
	}


	public function testIsAllowed_unknownResource_setDefault()
	{
		$this->manager->shouldReceive('findTargetResources')->once()->with('user')->andReturnNull()->getMock();

		$this->authorizator->setDefault(true);

		Assert::true($this->authorizator->isAllowed($this->user, 'user', 'view'));
	}


	public function testIsAllowed_targetResource_unknown()
	{
		$this->manager->shouldReceive('findTargetResources')->once()->andReturnNull()->getMock();

		Assert::false($this->authorizator->isAllowed($this->user, new \stdClass, 'view'));
	}


	public function testIsAllowed_targetResource_unknown_debugMode()
	{
		$this->manager->shouldReceive('findTargetResources')->once()->andReturnNull()->getMock();

		$this->authorizator->setDebugMode(true);

		Assert::exception(function() {
			$this->authorizator->isAllowed($this->user, new \stdClass, 'view');
		}, 'Carrooi\Security\UnknownResourceObjectException', 'Object stdClass is not registered security resource target.');
	}


	public function testIsAllowed_targetResource()
	{
		$booksAuthorizator = \Mockery::mock('Carrooi\Security\Authorizator\IResourceAuthorizator')
			->shouldReceive('getActions')->once()->andReturn('*')->getMock()
			->shouldReceive('isAllowed')->once()->andReturn(true)->getMock();

		$book = new \stdClass;

		$this->manager
			->shouldReceive('findTargetResources')->once()->with($book)->andReturn(['book'])->getMock()
			->shouldReceive('getAuthorizator')->once()->with('book')->andReturn($booksAuthorizator)->getMock();

		Assert::true($this->authorizator->isAllowed($this->user, $book, 'edit'));
	}


	public function testIsAllowed_targetResource_many()
	{
		$booksAuthorizator = \Mockery::mock('Carrooi\Security\Authorizator\IResourceAuthorizator')
			->shouldReceive('getActions')->once()->andReturn('*')->getMock()
			->shouldReceive('isAllowed')->once()->andReturn(true)->getMock();

		$chaptersAuthorizator = \Mockery::mock('Carrooi\Security\Authorizator\IResourceAuthorizator')
			->shouldReceive('getActions')->once()->andReturn('*')->getMock()
			->shouldReceive('isAllowed')->once()->andReturn(false)->getMock();

		$book = new \stdClass;

		$this->manager
			->shouldReceive('findTargetResources')->once()->with($book)->andReturn(['book', 'chapter'])->getMock()
			->shouldReceive('getAuthorizator')->once()->with('book')->andReturn($booksAuthorizator)->getMock()
			->shouldReceive('getAuthorizator')->once()->with('chapter')->andReturn($chaptersAuthorizator)->getMock();

		Assert::false($this->authorizator->isAllowed($this->user, $book, 'edit'));
	}


	public function testIsAllowed_magicMethod()
	{
		$magicAuthorizator = \Mockery::mock('CarrooiTests\Security\Authorization\MagicAuthorizator')
			->shouldReceive('getActions')->twice()->andReturn('*')->getMock()
			->shouldReceive('isEditAllowed')->once()->andReturn(true)->getMock()
			->shouldReceive('isAllowed')->once()->andReturn(false)->getMock();

		$this->manager
			->shouldReceive('findTargetResources')->twice()->with('book')->andReturn(['book'])->getMock()
			->shouldReceive('getAuthorizator')->twice()->with('book')->andReturn($magicAuthorizator)->getMock();

		Assert::true($this->authorizator->isAllowed($this->user, 'book', 'edit'));
		Assert::false($this->authorizator->isAllowed($this->user, 'book', 'add'));
	}

}


/**
 * @author David Kudera
 */
class MagicAuthorizator implements IResourceAuthorizator
{


	/**
	 * @return string
	 */
	public function getActions()
	{
		return '*';
	}


	/**
	 * @param \Carrooi\Security\User\User $user
	 * @param string $action
	 * @param mixed $data
	 * @return bool
	 */
	public function isAllowed(User $user, $action, $data = null) {}


	/**
	 * @param \Carrooi\Security\User\User $user
	 * @param null $data
	 * @return bool
	 */
	public function isEditAllowed(User $user, $data = null) {}

}


run(new AuthorizatorTest);
