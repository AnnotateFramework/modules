<?php

namespace Annotate\Modules;


interface IModuleFactory
{

	/** @return ModuleDefinition[] */
	function provides();



	function setModulesRegister(ModulesRegister $modulesRegister);



	function register();

}
