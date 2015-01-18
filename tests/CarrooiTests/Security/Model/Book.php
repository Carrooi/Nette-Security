<?php

namespace CarrooiTests\Security\Model;

use Nette\Object;

/**
 *
 * @author David Kudera
 */
class Book extends Object
{


	/** @var int */
	public $userId;


	/**
	 * @param int $userId
	 */
	public function __construct($userId = null)
	{
		$this->userId = $userId;
	}

}
