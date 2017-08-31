<?php

namespace Annotate\Modules;

use Annotate\Framework\Application\Components\Container;
use Annotate\Framework\Utils\Strings;
use Annotate\Modules\Application\ModularPresenter;
use Nette\Application\UI\ComponentReflection;
use Nette\Application\UI\Presenter;
use Nette\ComponentModel\IComponent;
use Nette\Object;
use Nette\Reflection\ClassType;
use Nette\Reflection\Method;


abstract class BaseModule extends Object implements IModule
{

	/** @var ModularPresenter */
	protected $presenter;



	public function run(Presenter $presenter)
	{
		$this->presenter = $presenter;
		$view = $presenter->getAction();
		$buildMethod = $this->formatBuildMethod($view);


		if (!method_exists($this, $buildMethod)) {
			$reflection = ClassType::from($this);
			$directory = dirname($reflection->getFileName());
			if (!file_exists($directory . "/templates/" . $view . ".latte")) {
				throw new Exceptions\ModuleViewNotFoundException("Module view '$view' not found");
			}

			return;
		}

		$this->runBuildMethod($this->reflection->getMethod($buildMethod));

	}



	/**
	 * @hidden
	 */
	public function buildDefault()
	{
	}



	private function formatBuildMethod($view)
	{
		return "build" . ucfirst($view);
	}



	private function runBuildMethod(Method $method)
	{
		$templateAnnotation = $method->getAnnotation('template');

		if ($templateAnnotation) {
			$this->useTemplate($templateAnnotation);
		}

		$methodParameters = $method->getParameters();
		$parameters = [];

		foreach ($methodParameters as $parameter) {
			$parameterName = $parameter->getName();
			$parameters[$parameterName] = $this->presenter->getParameter($parameterName);
		}
		$method->invokeArgs($this, $parameters);
	}



	protected function useTemplate($name)
	{
		$this->presenter->setTemplateFile($name);
	}



	public function redirect($code, $destination = NULL, $args = [])
	{
		if (!is_numeric($code)) {
			$args = is_array($destination) ? $destination : [];
			$destination = $code;
			$code = NULL;
		}

		if (Strings::startsWith($destination, "#")) {
			$raw = explode(":", str_replace("#", NULL, $destination));
			$cmsmodule = $raw[0];
			$destination = isset($raw[1]) ? $raw[1] : "default";
			$args["cmsmodule"] = $cmsmodule;
		}

		if ($code) {
			$this->presenter->redirect($code, $destination, $args);
		} else {
			$this->presenter->redirect($destination, $args);
		}
	}



	public function add(IComponent $component, $name)
	{
		$this->getContainer()->addComponent($component, $name);
	}



	public function isAjax()
	{
		return $this->presenter->isAjax();
	}



	public function redrawControl($snippet = NULL, $redraw = TRUE)
	{
		$this->presenter->redrawControl($snippet, $redraw);
	}



	public function flashMessage($message, $type = 'info')
	{
		$this->presenter->flashMessage($message, $type);
		$this->presenter->redrawControl('flashes');
	}



	public function link($destination, $args = [])
	{
		return $this->presenter->link($destination, $args);
	}



	/**
	 * @return Container
	 */
	public function getContainer()
	{
		return $this->presenter->getComponent("container");
	}



	public function tryCall($method, $params)
	{
		$rc = new ComponentReflection($this);
		if ($rc->hasMethod($method)) {
			$rm = $rc->getMethod($method);
			if ($rm->isPublic() && !$rm->isAbstract() && !$rm->isStatic()) {
				$rm->invokeArgs($this, $rc->combineArgs($rm, $params));
				return TRUE;
			}
		}
		return FALSE;
	}

}
