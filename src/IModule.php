<?php

namespace Annotate\Modules;

use Nette\Application\UI\Presenter;


interface IModule
{

	const TYPE_FRONTEND = "frontend";

	const TYPE_BACKEND = "backend";

	const TYPE_HIDDEN = "hidden";



	function run(Presenter $presenter);



	function tryCall($formatSignalMethod, $params);

}
