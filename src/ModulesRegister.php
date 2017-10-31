<?php

namespace Annotate\Modules;

use Annotate\Modules\Exceptions\ModuleCreationException;
use Annotate\Modules\Exceptions\ModuleNotFoundException;
use Annotate\Templating\TemplateFactory;
use Kdyby\Events\Subscriber;
use Nette\DI\Container;
use Nette\DI\MissingServiceException;
use Nette\Object;
use Nette\Reflection\ClassType;
use UnexpectedValueException;


class ModulesRegister extends Object implements Subscriber
{

	private $providers = [];

	private $activeModuleName;

	/**
	 * @var IModule|BaseModule
	 */
	private $module;

	/**
	 * @var Container
	 */
	private $container;



	public function __construct(Container $container)
	{
		$this->container = $container;
	}



	/**
	 * @param  string
	 *
	 * @return \Annotate\Modules\IModule
	 */
	public function getModule($name)
	{
		$factory = $this->getFactoryForModule($name);
		$definition = $this->getModuleDefinition($name);

		$factoryMethod = "createModule" . ucfirst($name);

		if (method_exists($factory, $factoryMethod)) {
			$module = $factory->$factoryMethod();
		} else {
			$moduleClass = $definition->getClass();
			$module = $this->createModule($moduleClass);
		}

		if (!$module) {
			$factoryClass = get_class($factory);
			throw new UnexpectedValueException(
				"Method '$factoryClass:createModule' does not return or create desired module '$name'"
			);
		}
		if (!($module instanceof IModule)) {
			$moduleClass = get_class($module);
			throw new UnexpectedValueException("'$moduleClass' is not instance of Annotate\\Modules\\IModule");
		}
		$this->module = $module;
		$this->activeModuleName = $name;

		return $module;
	}



	/**
	 * @param  string
	 * @throws Exceptions\ModuleNotFoundException
	 * @return IModuleFactory
	 */
	private function getFactoryForModule($name)
	{
		if (isset($this->providers[$name])) {
			return $this->providers[$name]['factory'];
		}
		throw new ModuleNotFoundException("No factory for module '$name' found.");
	}



	/**
	 * @param  string
	 * @return ModuleDefinition
	 */
	private function getModuleDefinition($name)
	{
		return $this->providers[$name]['definition'];
	}



	public function createModule($classname)
	{
		$reflection = ClassType::from($classname);
		$constructor = $reflection->hasMethod("__construct") ? $reflection->getMethod("__construct") : NULL;
		$constructorParameters = [];
		if ($constructor) {
			foreach ($constructor->getParameters() as $parameter) {
				if (!$parameter->getClassName() && !$parameter->isOptional()) {
					throw new ModuleCreationException(
						"Parameter '{$parameter->getName()}' in method '$classname::__construct()' has no type hint, so its value must be specified."
					);
				}
				try {
					$constructorParameters[$parameter->getName()] = $this->container->getByType(
						$parameter->getClassName()
					);
				} catch (MissingServiceException $e) {
					if (!$parameter->isOptional()) {
						throw new ModuleCreationException(
							"Cannot instantiate module '$classname'. Missing service of type '{$parameter->getClassName()}'",
							0,
							$e
						);
					}
				}
			}
		}

		return $reflection->newInstanceArgs($constructorParameters);
	}



	public function registerModuleFactory(IModuleFactory $factory)
	{
		$factory->register();
		$factory->setModulesRegister($this);
		foreach ($factory->provides() as $definition) {
			$this->providers[$definition->getUrl()] = [
				'factory' => $factory,
				'definition' => $definition,
			];
		}
	}



	public function getSubscribedEvents()
	{
		return [
			TemplateFactory::class . "::onLoadTemplate"
		];
	}



	public function onLoadTemplate(TemplateFactory $templateFactory, $templateFile, $name)
	{
		if (!$this->module) {
			return;
		}
		$modulePath = dirname(ClassType::from($this->module)->getFileName());

		$path = $modulePath . "/templates/" . $name . "/" . $templateFile . ".latte";
		if (file_exists($path)) {
			$templateFactory->addTemplate($path);
		}
		$path = $modulePath . "/templates/" . $templateFile . ".latte";
		if (file_exists($path)) {
			$templateFactory->addTemplate($path);
		}
	}



	/**
	 * @return ModuleDefinition[]
	 */
	public function getDefinitions()
	{
		$definitions = [];

		foreach ($this->providers as $provider) {
			/** @var ModuleDefinition $defintion */
			$defintion = $provider['definition'];
			$definitions[$defintion->getUrl()] = $defintion;
		}

		return $definitions;
	}



	/**
	 * @return string
	 */
	public function getActiveModuleName()
	{
		return $this->activeModuleName;
	}

}
