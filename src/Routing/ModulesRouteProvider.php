<?php

namespace Annotate\Modules\Routing;

use Annotate\Framework\Routing\IRouteProvider;
use Nette\Application\IRouter;
use Nette\Application\Routers\Route;


class ModulesRouteProvider implements IRouteProvider
{

	/** @var bool */
	private $secured;



	public function __construct($secured)
	{
		$this->secured = $secured;
	}



	public function register(IRouter $router)
	{
		$router[] = new Route(
			"<cmsmodule>[/<action>][/<id [0-9]+>]", [
				"presenter" => "Frontend",
				"cmsmodule" => "homepage",
				"action" => "default",
				"id" => NULL,
			], $this->secured ? Route::SECURED : 0
		);
	}

}
