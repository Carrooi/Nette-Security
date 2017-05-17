<?php

/**
 * Test: Carrooi\Security\Authorization\Authorizator
 *
 * @testCase CarrooiTests\Security\Authorizator_PresenterTest
 * @author David Kudera
 */

namespace CarrooiTests\Security\Authorization;

use Carrooi\Security\Authorization\Authorizator;
use Carrooi\Security\Authorization\IResourceAuthorizator;
use Carrooi\Security\Authorization\ResourcesManager;
use Carrooi\Security\Authorization\TPresenterAuthorization;
use Carrooi\Security\StrictModeException;
use Carrooi\Security\User\User;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\MethodReflection;
use Nette\Application\UI\Presenter;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__. '/../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class Authorizator_PresenterTest extends TestCase
{


	/** @var \Carrooi\Security\User\User|\Mockery\MockInterface */
	private $user;

	/** @var \Carrooi\Security\Authorization\ResourcesManager|\Mockery\MockInterface */
	private $manager;

	/** @var \Carrooi\Security\Authorization\Authorizator */
	private $authorizator;


	public function setUp()
	{
		$this->user = \Mockery::mock(User::class);
		$this->manager = \Mockery::mock(ResourcesManager::class);

		$this->authorizator = new Authorizator($this->manager);
	}


	public function tearDown()
	{
		\Mockery::close();
	}


	public function testIsAllowed_signal_strict_noAction()
	{
		$presenter = new SuperPresenter;

		$this->authorizator->setSignalsMode(Authorizator::MODE_STRICT);

		Assert::exception(function() use ($presenter) {
			$this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('handleNoAction')], null);
		}, StrictModeException::class, SuperPresenter::class. '::handleNoAction(): Missing action annotation when security for signals is at strict mode.');
	}


	public function testIsAllowed_component_strict_noAction()
	{
		$presenter = new SuperPresenter;

		$this->authorizator->setComponentsMode(Authorizator::MODE_STRICT);

		Assert::exception(function() use ($presenter) {
			$this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('createComponentNoAction')], null);
		}, StrictModeException::class, SuperPresenter::class. '::createComponentNoAction(): Missing action annotation when security for components is at strict mode.');
	}


	public function testIsAllowed_action_strict_noResourceOrAction()
	{
		$presenter = new SuperPresenter;

		$this->authorizator->setActionsMode(Authorizator::MODE_STRICT);

		Assert::exception(function() use ($presenter) {
			$this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('actionNoResource')], null);
		}, StrictModeException::class, SuperPresenter::class. '::actionNoResource(): Missing resource or action annotation when security for actions is at strict mode.');

		Assert::exception(function() use ($presenter) {
			$this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('actionNoAction')], null);
		}, StrictModeException::class, SuperPresenter::class. '::actionNoAction(): Missing resource or action annotation when security for actions is at strict mode.');
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
		$booksAuthorizator = \Mockery::mock(IResourceAuthorizator::class)
			->shouldReceive('getActions')->twice()->andReturn('*')->getMock()
			->shouldReceive('isAllowed')->once()->with($this->user, 'edit', null)->andReturn(false)->getMock()
			->shouldReceive('isAllowed')->once()->with($this->user, 'view', null)->andReturn(true)->getMock();

		$this->manager
			->shouldReceive('findTargetResources')->twice()->with('book')->andReturn(['book'])->getMock()
			->shouldReceive('getAuthorizator')->twice()->with('book')->andReturn($booksAuthorizator)->getMock();

		$presenter = new SuperPresenter;

		$this->authorizator->setActionsMode(Authorizator::MODE_ON);

		Assert::false($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('actionEdit')], null));
		Assert::true($this->authorizator->isAllowed($this->user, [$presenter, $presenter->getReflection()->getMethod('actionView')], null));
	}


	public function testIsAllowed_trait_methodRequirements()
	{
		$this->user->shouldReceive('isAllowed')->twice()->andReturnValues([true, false]);

		$presenter = new SuperPresenter($this->user);

		$this->authorizator->setActionsMode(Authorizator::MODE_ON);

		$presenter->checkRequirements($presenter->getReflection()->getMethod('actionView'));

		Assert::exception(function() use ($presenter) {
			$presenter->checkRequirements($presenter->getReflection()->getMethod('actionEdit'));
		}, ForbiddenRequestException::class);
	}


	public function testIsAllowed_trait_signalRequirements()
	{
		$this->user->shouldReceive('isAllowed')->twice()->andReturnValues([true, false]);

		$presenter = new SuperPresenter($this->user);
		$presenter->changeAction('add');

		$this->authorizator->setSignalsMode(Authorizator::MODE_ON);

		$presenter->checkRequirements($presenter->getReflection()->getMethod('handleAllActions'));

		Assert::exception(function() use ($presenter) {
			$presenter->checkRequirements($presenter->getReflection()->getMethod('handleArrayActions'));
		}, ForbiddenRequestException::class);
	}


	public function testIsAllowed_trait_componentRequirements()
	{
		$this->user->shouldReceive('isAllowed')->twice()->andReturnValues([true, false]);

		$presenter = new SuperPresenter($this->user);
		$presenter->changeAction('add');

		$this->authorizator->setComponentsMode(Authorizator::MODE_ON);

		$presenter->checkComponentRequirements('allActions');

		Assert::exception(function() use ($presenter) {
			$presenter->checkComponentRequirements('arrayActions');
		}, ForbiddenRequestException::class);
	}

}


/**
 * @author David Kudera
 */
class SuperPresenter extends Presenter
{

	use TPresenterAuthorization;

	private $user;

	public function __construct($user = null)
	{
		parent::__construct();

		$this->user = $user;
	}


	public function checkRequirements($element)
	{
		if ($element instanceof MethodReflection) {
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
	 * @action(*)
	 */
	public function handleAllActions() {}

	/**
	 * @action(detail, edit, default)
	 */
	public function handleArrayActions() {}

	public function createComponentNoAction() {}

	/**
	 * @action(*)
	 */
	public function createComponentAllActions() {}

	/**
	 * @action(detail, edit, default)
	 */
	public function createComponentArrayActions() {}

	/**
	 * @action(default)
	 */
	public function actionNoResource() {}

	/**
	 * @resource(book)
	 */
	public function actionNoAction() {}

	/**
	 * @resource(book)
	 * @action(edit)
	 */
	public function actionEdit() {}

	/**
	 * @resource(book)
	 * @action(view)
	 */
	public function actionView() {}

}


(new Authorizator_PresenterTest)->run();
