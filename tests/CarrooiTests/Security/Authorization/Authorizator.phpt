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
use Carrooi\Security\Authorization\TPresenterAuthorization;
use CarrooiTests\Security\Model\Book;
use CarrooiTests\Security\Model\Books;
use CarrooiTests\SecurityMocks\UserMock;
use CarrooiTests\SecurityMocks\UserStorageMock;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Presenter;
use Nette\Reflection\Method;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__. '/../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class AuthorizatorTest extends TestCase
{


	/** @var \Nette\DI\Container|\Mockery\MockInterface */
	private $container;

	/** @var \Carrooi\Security\Authorization\ResourcesManager */
	private $manager;

	/** @var \Carrooi\Security\Authorization\Authorizator */
	private $authorizator;

	/** @var \CarrooiTests\SecurityMocks\UserMock */
	private $user;


	public function setUp()
	{
		$this->container = \Mockery::mock('Nette\DI\Container');
		$this->manager = new ResourcesManager($this->container);
		$this->authorizator = new Authorizator($this->manager);
		$this->user = new UserMock(new UserStorageMock, $this->authorizator);
	}


	public function tearDown()
	{
		\Mockery::close();
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


	public function testIsAllowed_otherResource()
	{
		$resourceAuthorizator = new DefaultResourceAuthorizator;
		$resourceAuthorizator
			->addAction('*', null, true);

		$this->manager->addAuthorizator('*', $resourceAuthorizator);

		Assert::false($this->authorizator->isAllowed($this->user, 'user', 'view'));

		$this->user->getStorage()->setAuthenticated(true);

		Assert::true($this->authorizator->isAllowed($this->user, 'user', 'view'));
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


	public function testIsAllowed_registeredTargetResource()
	{
		$authorizator = \Mockery::mock('Carrooi\Security\IResourceAuthorizator')
			->shouldReceive('isAllowed')->once()->andReturn(true)->getMock();

		$this->container->shouldReceive('getByType')->once()->with(get_class($authorizator))->andReturn($authorizator);

		$this->manager->addTargetResource('CarrooiTests\Security\Model\Book', 'book');
		$this->manager->registerAuthorizator('book', get_class($authorizator));

		Assert::true($this->authorizator->isAllowed($this->user, new Book(5), 'view'));
	}


	public function testIsAllowed_targetResource_subclass()
	{
		$this->manager->addTargetResource('CarrooiTests\Security\Model\Book', 'book');
		$this->manager->addAuthorizator('book', new Books);

		$this->user->setId(5);
		$this->user->setRoles(['writer']);
		$this->user->getStorage()->setAuthenticated(true);

		Assert::true($this->authorizator->isAllowed($this->user, new SuperBook(5), 'edit'));
	}


	public function testIsAllowed_signal_strict_noAction()
	{
		$presenter = new SuperPresenter;

		$this->authorizator->setSignalsMode(Authorizator::MODE_STRICT);

		Assert::exception(function() use ($presenter) {
			$this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('handleNoAction')], null);
		}, 'Carrooi\Security\StrictModeException', 'CarrooiTests\Security\Authorization\SuperPresenter::handleNoAction(): Missing action annotation when security for signals is at strict mode.');
	}


	public function testIsAllowed_component_strict_noAction()
	{
		$presenter = new SuperPresenter;

		$this->authorizator->setComponentsMode(Authorizator::MODE_STRICT);

		Assert::exception(function() use ($presenter) {
			$this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('createComponentNoAction')], null);
		}, 'Carrooi\Security\StrictModeException', 'CarrooiTests\Security\Authorization\SuperPresenter::createComponentNoAction(): Missing action annotation when security for components is at strict mode.');
	}


	public function testIsAllowed_action_strict_noResourceOrAction()
	{
		$presenter = new SuperPresenter;

		$this->authorizator->setActionsMode(Authorizator::MODE_STRICT);

		Assert::exception(function() use ($presenter) {
			$this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('actionNoResource')], null);
		}, 'Carrooi\Security\StrictModeException', 'CarrooiTests\Security\Authorization\SuperPresenter::actionNoResource(): Missing resource or action annotation when security for actions is at strict mode.');

		Assert::exception(function() use ($presenter) {
			$this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('actionNoAction')], null);
		}, 'Carrooi\Security\StrictModeException', 'CarrooiTests\Security\Authorization\SuperPresenter::actionNoAction(): Missing resource or action annotation when security for actions is at strict mode.');
	}


	public function testIsAllowed_signal_actionEmpty()
	{
		$presenter = new SuperPresenter;
		$presenter->changeAction('default');

		$this->authorizator->setSignalsMode(Authorizator::MODE_ON);

		Assert::true($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('handleNoAction')], null));
		Assert::true($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('handleAllActions')], null));
	}


	public function testIsAllowed_component_actionEmpty()
	{
		$presenter = new SuperPresenter;
		$presenter->changeAction('default');

		$this->authorizator->setComponentsMode(Authorizator::MODE_ON);

		Assert::true($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('createComponentNoAction')], null));
		Assert::true($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('createComponentAllActions')], null));
	}


	public function testIsAllowed_signal_arrayActions()
	{
		$presenter = new SuperPresenter;
		$presenter->changeAction('default');

		$this->authorizator->setSignalsMode(Authorizator::MODE_ON);

		Assert::true($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('handleArrayActions')], null));

		$presenter->changeAction('add');

		Assert::false($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('handleArrayActions')], null));
	}


	public function testIsAllowed_component_arrayActions()
	{
		$presenter = new SuperPresenter;
		$presenter->changeAction('default');

		$this->authorizator->setComponentsMode(Authorizator::MODE_ON);

		Assert::true($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('createComponentArrayActions')], null));

		$presenter->changeAction('add');

		Assert::false($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('createComponentArrayActions')], null));
	}


	public function testIsAllowed_signal_modeOff()
	{
		$presenter = new SuperPresenter;
		$presenter->changeAction('add');

		$this->authorizator->setSignalsMode(Authorizator::MODE_OFF);

		Assert::true($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('handleArrayActions')], null));
	}


	public function testIsAllowed_component_modeOff()
	{
		$presenter = new SuperPresenter;
		$presenter->changeAction('add');

		$this->authorizator->setComponentsMode(Authorizator::MODE_OFF);

		Assert::true($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('createComponentArrayActions')], null));
	}


	public function testIsAllowed_action_modeOff()
	{
		$presenter = new SuperPresenter;

		$this->authorizator->setActionsMode(Authorizator::MODE_OFF);

		Assert::true($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('actionNoResource')], null));
		Assert::true($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('actionNoAction')], null));
		Assert::true($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('actionEdit')], null));
	}


	public function testIsAllowed_action()
	{
		$presenter = new SuperPresenter;

		$this->manager->addAuthorizator('book', new Books);
		$this->authorizator->setActionsMode(Authorizator::MODE_ON);

		Assert::false($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('actionEdit')], null));
		Assert::true($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('actionView')], null));
	}


	public function testIsAllowed_action_objectResource()
	{
		$presenter = new SuperPresenter;

		$this->manager->addTargetResource('CarrooiTests\Security\Model\Book', 'book');
		$this->manager->addAuthorizator('book', new Books);

		$this->authorizator->setActionsMode(Authorizator::MODE_ON);

		$this->user->setId(5);
		$this->user->setRoles(['writer']);
		$this->user->getStorage()->setAuthenticated(true);

		Assert::true($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('actionEditObjectResource')], null));
	}


	public function testIsAllowed_trait_methodRequirements()
	{
		$presenter = new SuperPresenter;
		$presenter->user = $this->user;

		$this->manager->addTargetResource('CarrooiTests\Security\Model\Book', 'book');
		$this->manager->addAuthorizator('book', new Books);

		$this->authorizator->setActionsMode(Authorizator::MODE_ON);

		$presenter->checkRequirements($presenter->getReflection()->getMethod('actionView'));

		Assert::exception(function() use ($presenter) {
			$presenter->checkRequirements($presenter->getReflection()->getMethod('actionEdit'));
		}, 'Nette\Application\ForbiddenRequestException');
	}


	public function testIsAllowed_trait_signalRequirements()
	{
		$presenter = new SuperPresenter;
		$presenter->user = $this->user;
		$presenter->changeAction('add');

		$this->authorizator->setSignalsMode(Authorizator::MODE_ON);

		$presenter->checkRequirements($presenter->getReflection()->getMethod('handleAllActions'));

		Assert::exception(function() use ($presenter) {
			$presenter->checkRequirements($presenter->getReflection()->getMethod('handleArrayActions'));
		}, 'Nette\Application\ForbiddenRequestException');
	}


	public function testIsAllowed_trait_componentRequirements()
	{
		$presenter = new SuperPresenter;
		$presenter->user = $this->user;
		$presenter->changeAction('add');

		$this->authorizator->setComponentsMode(Authorizator::MODE_ON);

		$presenter->checkComponentRequirements('allActions');

		Assert::exception(function() use ($presenter) {
			$presenter->checkComponentRequirements('arrayActions');
		}, 'Nette\Application\ForbiddenRequestException');
	}

}


/**
 * @author David Kudera
 */
class SuperBook extends Book {}


/**
 * @author David Kudera
 */
class SuperPresenter extends Presenter
{

	use TPresenterAuthorization;

	public $user;

	public function checkRequirements($element)
	{
		if ($element instanceof Method) {
			if (!$this->checkMethodRequirements($element)) {
				throw new ForbiddenRequestException;
			}
		}
	}

	protected function createComponent($name)
	{
		$this->checkComponentRequirements($name);
		return parent::createComponent($name);
	}

	public function getUser()
	{
		return $this->user;
	}

	public function handleNoAction() {}

	/**
	 * @action *
	 */
	public function handleAllActions() {}

	/**
	 * @action detail, edit, default
	 */
	public function handleArrayActions() {}

	public function createComponentNoAction() {}

	/**
	 * @action *
	 */
	public function createComponentAllActions() {}

	/**
	 * @action detail, edit, default
	 */
	public function createComponentArrayActions() {}

	/**
	 * @action default
	 */
	public function actionNoResource() {}

	/**
	 * @resource book
	 */
	public function actionNoAction() {}

	/**
	 * @resource book
	 * @action edit
	 */
	public function actionEdit() {}

	/**
	 * @resource book
	 * @action view
	 */
	public function actionView() {}

	/**
	 * @resource ::getBook()
	 * @action edit
	 */
	public function actionEditObjectResource() {}

	public function getBook()
	{
		return new Book(5);
	}

}


run(new AuthorizatorTest);
