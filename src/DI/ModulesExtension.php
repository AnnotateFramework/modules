<?php

namespace Annotate\Modules\DI;

use Annotate\Modules\ModuleLinksExtractor;
use Annotate\Modules\ModulesRegister;
use Annotate\Modules\Routing\ModulesRouteProvider;
use Kdyby\Events\DI\EventsExtension;
use Nette\DI\CompilerExtension;


class ModulesExtension extends CompilerExtension
{

	const TAG_MODULES_FACTORY = 'modules.factory';



	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$factories = array_keys($builder->findByTag(self::TAG_MODULES_FACTORY));

		$modulesRegister = $builder->getDefinition($this->prefix('register'));


		foreach ($factories as $factory) {
			$modulesRegister->addSetup('registerModuleFactory', ['@' . $factory]);
		}

		$builder->addDefinition($this->prefix('moduleLinksExtractor'))
			->setClass(ModuleLinksExtractor::class);

	}



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('routesProvider'))
			->setClass(ModulesRouteProvider::class, [
				'secured' => isset($builder->parameters['https']) ? $builder->parameters['https'] : FALSE,
			]);

		$builder->addDefinition($this->prefix('register'))
			->setClass(ModulesRegister::class)
			->addTag(EventsExtension::TAG_SUBSCRIBER);
	}

}
