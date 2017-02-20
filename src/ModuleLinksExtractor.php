<?php

namespace Annotate\Modules;

use Annotate\Backend\UI\Link;
use Annotate\Framework\Utils\Strings;
use Nette\Object;
use Nette\Reflection\ClassType;
use Nette\Reflection\Method;


class ModuleLinksExtractor extends Object
{

	const BUILD_METHOD_PREFIX = 'build';

	/** @var ModulesRegister */
	private $modulesRegister;

	/**
	 * Already extracted links - used as internal cache
	 *
	 * @var array
	 */
	private $extracted = [];



	public function __construct(ModulesRegister $modulesRegister)
	{
		$this->modulesRegister = $modulesRegister;
	}



	public function extractForModule($url)
	{

		if (!isset($this->extracted[$url])) {
			$this->extracted[$url] = $this->extract($url);
		}

		return $this->extracted[$url];
	}



	private function extract($moduleName)
	{
		$links = [];
		$definition = $this->modulesRegister->getDefinitions()[$moduleName];
		$reflection = ClassType::from($definition->getClass());
		/** @var Method[] $methods */
		$methods = $reflection->getMethods();
		foreach ($methods as $method) {
			$methodName = $method->getName();
			if (Strings::startsWith($methodName, self::BUILD_METHOD_PREFIX)) {
				if ($method->hasAnnotation('hidden')) {
					continue;
				}
				$url = "#" . strtolower($moduleName) . ":" . self::formatBuildMethodUrl($methodName);
				$text = self::formatBuildMethodName($methodName);

				$annotation = $method->getAnnotation("view");
				if ($annotation) {
					if (isset($annotation->text)) {
						$text = $annotation->text;
					}
					if (isset($annotation->icon)) {
						$icon = $annotation->icon;
					}
				}

				$links[$url] = new Link($url, $text, isset($icon) ? $icon : NULL);
			}
		}

		return $links;
	}



	private static function formatBuildMethodUrl($methodName)
	{
		return Strings::toDashes(str_replace(self::BUILD_METHOD_PREFIX, NULL, $methodName));
	}



	private static function formatBuildMethodName($methodName)
	{
		return str_replace('-', ' ', ucfirst(Strings::toDashes(str_replace(self::BUILD_METHOD_PREFIX, NULL, $methodName))));
	}

}
