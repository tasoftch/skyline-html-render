<?php

namespace Skyline\HTMLRender\Exception;


use Skyline\Application\Exception\RenderResponseException;
use Skyline\Render\Exception\RenderException;

class ComponentNotFoundException extends RenderException
{
    /** @var string */
    private $componentName;

    /**
     * @return string
     */
    public function getComponentName(): string
    {
        return $this->componentName;
    }

    /**
     * @param string $componentName
     */
    public function setComponentName(string $componentName)
    {
        $this->componentName = $componentName;
    }
}