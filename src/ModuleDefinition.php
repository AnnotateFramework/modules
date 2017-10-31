<?php

namespace Annotate\Modules;

use Nette\Object;


class ModuleDefinition extends Object
{

	const TYPE_HIDDEN = 'hidden';

	private $class;

	private $url;

	private $type;

	private $icon;

	private $extra = [];


    /**
     * @param  string
     * @param  string
     * @param  string|NULL
     * @param  string|NULL
     * @param  array
     */
    public function __construct($class, $url, $icon = NULL, $type = NULL, $extra = [])
	{
		$this->class = $class;
		$this->url = $url;
		$this->icon = $icon;
		$this->type = $type;
		$this->extra = $extra;
	}



	public function getClass()
	{
		return $this->class;
	}



	public function getIcon()
	{
		return $this->icon;
	}



	public function getUrl()
	{
		return $this->url;
	}



	public function getType()
	{
		return $this->type;
	}



	public function getExtra()
	{
		return $this->extra;
	}

}
