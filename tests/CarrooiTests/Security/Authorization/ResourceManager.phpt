<?php

/**
 * Test: Carrooi\Security\Authorization\ResourceManager
 *
 * @testCase CarrooiTests\Security\ResourceManager
 * @author David Kudera
 */

namespace CarrooiTests\Security\Authorization;

use Carrooi\Security\Authorization\ResourcesManager;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__. '/../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class ResourceManagerTest extends TestCase
{


	/** @var \Nette\DI\Container|\Mockery\MockInterface */
	private $container;

	/** @var \Carrooi\Security\Authorization\ResourcesManager */
	private $manager;


	public function setUp()
	{
		$this->container = \Mockery::mock('Nette\DI\Container');
		$this->manager = new ResourcesManager($this->container);
	}


	public function tearDown()
	{
		\Mockery::close();
	}


	public function testFindTargetResources_string()
	{
		Assert::equal(['book'], $this->manager->findTargetResources('book'));
	}


	public function testFindTargetResources_invalid()
	{
		Assert::exception(function() {
			$this->manager->findTargetResources([]);
		}, 'Carrooi\Security\InvalidArgumentException', 'Security resource target can be only string or an object, array given.');
	}


	public function testFindTargetResources_exactClass()
	{
		$this->manager->addTargetResource('stdClass', 'book');

		Assert::equal(['book'], $this->manager->findTargetResources(new \stdClass));
	}


	public function testFindTargetResources_childrenClass()
	{
		$this->manager->addTargetResource('stdClass', 'book');

		$book = \Mockery::mock('stdClass');

		Assert::equal(['book'], $this->manager->findTargetResources($book));
	}


	public function testFindTargetResources_interface()
	{
		$this->manager->addTargetResource('Countable', 'book');

		$book = \Mockery::mock('Countable');

		Assert::equal(['book'], $this->manager->findTargetResources($book));
	}


	public function testFindTargetResources_many()
	{
		$this->manager->addTargetResource('Countable', 'book');
		$this->manager->addTargetResource('ArrayAccess', 'chapter');
		$this->manager->addTargetResource('Serializable', 'comment');

		$book = \Mockery::mock('Countable,ArrayAccess');

		Assert::equal(['book', 'chapter'], $this->manager->findTargetResources($book));
	}


	public function testGetAuthorizator_unknown()
	{
		Assert::null($this->manager->getAuthorizator('book'));
	}


	public function testGetAuthorizator_exactClass()
	{
		$authorizator = \Mockery::mock('Carrooi\Security\Authorization\IResourceAuthorizator');

		$this->manager->addAuthorizator('book', $authorizator);

		Assert::same($authorizator, $this->manager->getAuthorizator('book'));
	}


	public function testGetAuthorizator_others()
	{
		$authorizator = \Mockery::mock('Carrooi\Security\Authorization\IResourceAuthorizator');

		$this->manager->addAuthorizator('*', $authorizator);

		Assert::same($authorizator, $this->manager->getAuthorizator('book'));
	}


	public function testGetAuthorizator_registered()
	{
		$authorizator = \Mockery::mock('Carrooi\Security\Authorization\IResourceAuthorizator');

		$this->container->shouldReceive('getByType')->once()->with(get_class($authorizator))->andReturn($authorizator)->getMock();

		$this->manager->registerAuthorizator('book', get_class($authorizator));

		Assert::same($authorizator, $this->manager->getAuthorizator('book'));
	}

}


run(new ResourceManagerTest);
