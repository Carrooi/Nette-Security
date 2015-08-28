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


	public function testGetTargetResource_string()
	{
		Assert::same('book', $this->manager->getTargetResource('book'));
	}


	public function testGetTargetResource_exactClass()
	{
		$this->manager->addTargetResource('stdClass', 'book');

		Assert::same('book', $this->manager->getTargetResource(new \stdClass));
	}


	public function testGetTargetResource_childrenClass()
	{
		$this->manager->addTargetResource('stdClass', 'book');

		$book = \Mockery::mock('stdClass');

		Assert::same('book', $this->manager->getTargetResource($book));
	}


	public function testGetTargetResource_interface()
	{
		$this->manager->addTargetResource('Countable', 'book');

		$book = \Mockery::mock('Countable');

		Assert::same('book', $this->manager->getTargetResource($book));
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
