# Carrooi/Security

[![Build Status](https://img.shields.io/travis/Carrooi/Nette-Security.svg?style=flat-square)](https://travis-ci.org/Carrooi/Nette-Security)
[![Donate](https://img.shields.io/badge/donate-PayPal-brightgreen.svg?style=flat-square)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=FQUQ9LVAKADK8)

Extensible authorization built on top of [nette/security](https://github.com/nette/security).

This package came in handy if you want to create modular website and keep all pieces decoupled with "custom" checking 
for privileges.

Now you can really easily check if eg. given user is author of some book and so on..

This idea comes from [nette/addons](https://github.com/nette/web-addons.nette.org/blob/master/app/model/Authorizator.php) 
website.

## Installation

```
$ composer require carrooi/security
$ composer update
```

Then just enable nette extension in your config.neon:

```yaml
extensions:
	authorization: Carrooi\Security\DI\SecurityExtension
```

## Configuration

```yaml
extendsions:
	authorization: Carrooi\Security\DI\SecurityExtension

authorization:
	default: true

	resources:
		book:
			default: false
			
			actions:
				view: true
				add:
					loggedIn: true
				edit:
					roles: [admin]
				delete:
					roles: [admin]
```

Well, there is nothing modular.... Yet.... We just say that resource `book` has `view` action which is accessible to 
everyone, `add` to logged users and `edit` with `delete` actions to users with `admin` role.

There are also two `default` options. With the first one we say that each `->isAllowed()` call on unknown action will 
automatically return `true`. But the second `default` will overwrite this option for all `book` actions to `false`.

That means that eg. `->isAllowed('book', 'detail')` will return `false`, but `->isAllowed('user', 'detail')` `true`.

## Other resources and actions

If `default` option is not enough, you can create default resource or default action with asterisk.

```yaml
authorization:
	
	resources:
		favorites:
			actions:
				*:
					loggedIn: true
```

## Custom resource authorizator

Now lets create the same authorization for books by hand.

```yaml
services:

	- App\Model\Books

authorization:
	resources:
		book: App\Model\Books
```

**`App\Model\Books` must be registered service.**

```php
namespace App\Model;

use Carrooi\Security\Authorization\IResourceAuthorizator;
use Carrooi\Security\User\User;

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
		if ($action === 'view') {
			return true;
		}
		
		if ($action === 'add' && $user->isLoggedIn()) {
			return true;
		}

		if (in_array($action, ['edit', 'delete']) && $user->isInRole('admin')) {
			return true;
		}

		return false;
	}

}
```

## Use objects as resources

In previous code you may noticed unused argument `$data` in `isAllowed` method. Imagine that you want to allow all users 
to update or delete their own books. First thing you need to do, is register some kind of "translator" from objects to 
resource names (lets say mappers).

```yaml
authorization:
	targetResources:
		App\Model\Book: book
```

Now every time you pass `App\Model\Book` object as resource, it will be automatically translated to `book` resource, 
which will be then processed with your `App\Model\Books` service registered in previous example.

```php
namespace App\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;

/**
 * @author David Kudera
 */
class BooksPresenter extends BasePresenter
{

	// ...

	/**
	 * @param int $id
	 * @throws \Nette\Application\BadRequestException
	 * @throws \Nette\Application\ForbiddenRequestException
	 */
	public function actionEdit($id)
	{
		$this->book = $this->books->findOneById($id);
		if (!$this->book) {
			throw new BadRequestException;
		}
		if (!$this->getUser()->isAllowed($this->book, 'edit')) {
			throw new ForbiddenRequestException;
		}
	}

}
```

```php

// ...
class Books implements IResourceAuthorizator
{

	// ...
	public function isAllowed(User $user, $action, $data = null)
	{
		// ...

		if (
			in_array($action, ['edit', 'delete']) &&
			$data instanceof Book && 
			(
				$user->isInRole('admin') ||
				$data->getAuthor()->getId() === $user->getId()
			)
		) {
			return true;
		}

		return false;
	}

}
```

## Linking to presenter

```php
class BasePresenter extends Nette\Application\UI\Presenter
{

	use Carrooi\Security\Authorization\TPresenterAuthorization;
	
	public function checkRequirements($element)
	{
		if ($element instanceof Nette\Reflection\Method) {
			if (!$this->checkMethodRequirements($element)) {
				throw new Nette\Application\ForbiddenRequestException;
			}
		}
	}

}
```

Now you can simply use annotations for setting current resource and action

```php
class BookPresenter extends BasePresenter
{

	/**
	 * @resource book
	 * @action view
	 */
	public function actionDefault()
	{

	}

}
```

or with object as resource:

```php
class BookPresenter extends BasePresenter
{

	/**
	 * @resource ::getBook()
	 * @action edit
	 */
	public function actionEdit($id)
	{

	}
	
	public function getBook()
	{
		return $this->books->findOneById($this->getParameter('id'));
	}

}
```

## Securing presenter components and signals

You can restrict any component or signal to some action. With that no one can access for example edit form from add action.

```php
class BasePresenter extends Nette\Application\UI\Presenter
{

	use Carrooi\Security\Authorization\TPresenterAuthorization;
	
	public function checkRequirements($element)
	{
		// ...
	}
	
	protected function createComponent($name)
	{
		$this->checkComponentRequirements($name);
        return parent::createComponent($name);
	}

}
```

```php
class BookPresenter extends BasePresenter
{

	/**
	 * @action edit
	 */
	protected function createComponentEditForm()
	{
		
	}
	
	/**
	 * @action default, detail
	 */
	protected function createComponentFavoriteButton()
	{
	
	}
	
	/**
	 * @action *
	 */
	protected function createComponentReadLaterButton()
	{
	
	}

}
```

**Keep in mind that actions at components or signals are presenter actions, not actions at your authorization configuration.**

Now `editForm` component can be rendered only on `edit` action, `favoriteButton` only on `default` or `detail` actions and 
`readLaterButton` anywhere.

Same `@action` annotations can be used also for signals.

## Presenter security modes

By default this package will try to check action, render, handle and createComponent methods. But if you'll omit some 
annotations, nothing will happen and that method will be allowed. This can be changed by turning on strict mode.

```yaml
authorization:
	actions: strict
	signals: strict
	components: strict
```

Other options are `true` or `false`, where `true` is default value.

## Compiler extension

Your own DI compiler extensions can implement interface `Carrooi\Security\DI\ITargetResourcesProvider` for resource 
mappers.

```php
namespace App\DI;

use Carrooi\Security\DI\ITargetResourcesProvider;
use Nette\DI\CompilerExtension;

/**
 * @author David Kudera
 */
class AppExtension extends CompilerExtension implements ITargetResourcesProvider
{


	/**
	 * @return array
	 */
	public function getTargetResources()
	{
		return [
			'App\Model\Book' => 'book',
		];
	}

}
```

## Extending User class

Be carefull if you want to extend `Nette\Security\User` class, because `carrooi\security` already extends that class 
for it's own needs.

## Changelog

* 1.0.0
	+ Initial commit
	
* 1.0.1
	+ Added default resources and actions (asterisk)
	
* 1.0.2
	+ Looking if given object for authorization is subclass of some registered target resource
