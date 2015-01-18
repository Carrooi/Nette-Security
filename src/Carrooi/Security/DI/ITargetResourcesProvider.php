<?php

namespace Carrooi\Security\DI;

/**
 *
 * @author David Kudera
 */
interface ITargetResourcesProvider
{


	/**
	 * @return array
	 */
	public function getTargetResources();

}
