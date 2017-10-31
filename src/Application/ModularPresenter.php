<?php

namespace Annotate\Modules\Application;

use Annotate\Framework\Application\BasePresenter;
use Annotate\Framework\Application\Components\Container;
use Annotate\Framework\Utils\Strings;
use Annotate\Modules\Exceptions\ModuleNotFoundException;
use Annotate\Modules\IModule;
use InvalidArgumentException;
use Nette;
use Nette\Application;
use Nette\Application\BadRequestException;
use Nette\Application\UI\BadSignalException;
use Nette\Application\UI\InvalidLinkException;
use Nette\ComponentModel\IComponent;


class ModularPresenter extends BasePresenter
{

	/**
	 * @var string
	 * @persistent
	 */
	public $cmsmodule = "dashboard";

	/** @var \Annotate\Modules\ModulesRegister @inject */
	public $modulesRegister;

	/** @var IModule */
	private $module;



	public function setTemplateFile($name)
	{
		$this->templateFile = $name;
	}



	protected function startup()
	{
		parent::startup();

		$moduleName = $this->getModuleName();

		try {
			$this->module = $this->modulesRegister->getModule($moduleName);
			$this->module->run($this);
		} catch (ModuleNotFoundException $e) {
			throw new BadRequestException("Module '$moduleName' not found", 404, $e);
		}
	}



	public function checkRequirements($element)
	{
		/* @var $element \Nette\Application\UI\PresenterComponentReflection */
		if ($element->hasAnnotation("secured")) {
			if (!$this->user->isAllowed("Administration") && !$this->isLinkCurrent(
					"in",
					["cmsmodule" => "sign"]
				)
			) {
				if ($this->isAjax()) {
					$this->getHttpResponse()->setCode(Nette\Http\IResponse::S401_UNAUTHORIZED);
				} else {
					$this->redirect("in", ["cmsmodule" => "sign"]);
				}
			}
		}
	}



	protected function createComponent($name)
	{
		$ucname = ucfirst($name);
		$method = 'createComponent' . $ucname;
		if ($ucname !== $name && method_exists($this, $method) && $this->getReflection()->getMethod($method)->getName() === $method
		) {
			$component = $this->$method($name);
		} elseif (method_exists($this->module, $method)) {
			$component = $this->module->$method($name);
		} elseif ($this->getContainer()->getComponent($name, FALSE)) {
			$component = $this->getContainer()->getComponent($name);
		} else {
			throw new InvalidArgumentException("No factory method for component '$name' found");
		}
		if (!$component instanceof IComponent && !isset($this->components[$name])) {
			$class = get_class($this);
			throw new Nette\UnexpectedValueException(
				"Method $class::$method() did not return or create the desired component."
			);
		}

		return $component;
	}



	protected function createRequest($component, $destination, array $args, $mode)
	{
		if (Strings::startsWith($destination, "#")) {
			$parts = explode(":", $destination);
			$destination = isset($parts[1]) ? $parts[1] : "default";
			$args['cmsmodule'] = str_replace("#", NULL, $parts[0]);
		}

		return parent::createRequest($component, $destination, $args, $mode);
	}



	private function getModuleName()
	{
		return $this->getParameter("cmsmodule");
	}



	/**
	 * @return Container
	 */
	private function getContainer()
	{
		return $this->getComponent("container");
	}



	public function signalReceived($signal)
	{
		try {
			parent::signalReceived($signal);
		} catch (BadSignalException $e) {
			if (!$this->module->tryCall($this->formatSignalMethod($signal), $this->params)) {
				$class = get_class($this->module);
				throw new BadSignalException('There is no handler for signal "' . $signal . ' in class "' . $class . '""');
			}
		}
	}



	public function link($destination, $args = [])
	{
		if (Strings::startsWith($destination, '#')) {
			$parts = explode(':', $destination);
			$destination = 'Backend:';
			$args['cmsmodule'] = substr($parts[0], 1);
			$destination .= isset($parts[1]) ? $parts[1] : 'default';
			return parent::link($destination, $args);
		}

		try {
			return $this->getPresenter()->createRequest($this, $destination, is_array($args) ? $args : array_slice(func_get_args(), 1), 'link');
		} catch (InvalidLinkException $e) {
			if ($this->isSignalLinkError($e)) {
				$signal = substr($destination, 0, -1);
				if (method_exists($this->module, $this->formatSignalMethod($signal))) {
					return $this->link('this', $args) . '?do=' . $signal;
				}
			}
			return $this->handleInvalidLink($e);
		}
	}



	private function isSignalLinkError(InvalidLinkException $e)
	{
		return Strings::startsWith($e->getMessage(), 'Unknown signal');
	}

}
