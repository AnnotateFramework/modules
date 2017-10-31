<?php

namespace Annotate\Modules;


abstract class ModuleFactory implements IModuleFactory
{

	/**
	 * @var ModulesRegister
	 */
	protected $modulesRegister;



	public function setModulesRegister(ModulesRegister $modulesRegister)
	{
		$this->modulesRegister = $modulesRegister;
	}

}
