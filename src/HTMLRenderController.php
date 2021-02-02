<?php
/**
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Skyline\HTMLRender;


use Skyline\Component\Config\AbstractComponent;
use Skyline\HTMLRender\Exception\ComponentNotFoundException;
use Skyline\Render\Service\CompiledRenderController;
use TASoft\Service\ServiceManager;

class HTMLRenderController extends CompiledRenderController
{
	const DEFAULT_RENDER_NAME = 'html-render';

    private $compiledComponentsFilename;
    private $compiledComponentsInfo;

    private $publicURIPrefix;
    private $resourceDirectory;

    public function __construct($compiledRenderFilename, $compiledComponentsFilename, $publicURIPrefix, $resourceDirectory)
    {
        parent::__construct($compiledRenderFilename);
        $this->compiledComponentsFilename = $compiledComponentsFilename;
        $this->publicURIPrefix = $publicURIPrefix;
        $this->resourceDirectory = $resourceDirectory;
    }

    /**
     * @return string
     */
    public function getCompiledComponentsFilename()
    {
        return $this->compiledComponentsFilename;
    }

    /**
     * Returns the required component's html elements
     *
     * @param string $identifier
     * @return array
     * @throws ComponentNotFoundException
     */
    public function getComponentElements(string $identifier): array {
        if(NULL === $this->compiledComponentsInfo) {
            $this->compiledComponentsInfo = require $this->getCompiledComponentsFilename();
        }

        if(isset($this->compiledComponentsInfo[ $identifier ]) && ($renderInfo = &$this->compiledComponentsInfo[ $identifier ] )) {
            $elements = [];

            foreach($renderInfo as $key => &$ri) {
                if($key == AbstractComponent::COMP_REQUIREMENTS)
                    continue;

                if(!isset($ri["instance"])) {
                    $class = $ri[ AbstractComponent::COMP_ELEMENT_CLASS ];
                    $args = $ri[ AbstractComponent::COMP_ELEMENT_ARGUMENTS ] ?? NULL;

                    if($args) {
                        $sm = ServiceManager::generalServiceManager();
                        $ri["instance"] = new $class(...array_values($sm->mapArray($args)));
                        unset($ri[ AbstractComponent::COMP_ELEMENT_ARGUMENTS ]);
                    } else {
                        $ri["instance"] = new $class();
                    }
                    unset($ri[ AbstractComponent::COMP_ELEMENT_CLASS ]);
                }

                $elements[$key] = $ri["instance"];
            }
            return $elements;
        } else {
            $e = new ComponentNotFoundException("Could not find desired component $identifier", 4040);
            $e->setComponentName($identifier);
            throw $e;
        }
    }

    public function hasComponent(string $identifier): bool {
        if(NULL === $this->compiledComponentsInfo) {
            $this->compiledComponentsInfo = require $this->getCompiledComponentsFilename();
        }
        return isset($this->compiledComponentsInfo[$identifier]);
    }

    /**
     * Transforms an URI into a local filename if available
     *
     * @param string $URI
     * @param bool $prependPublicPrefix
     * @return string|null
     */
    public function getMappedLocalFilename(string $URI, bool $prependPublicPrefix = true): ?string {
        if(NULL === $this->compiledComponentsInfo) {
            $this->compiledComponentsInfo = require $this->getCompiledComponentsFilename();
        }

        if($prependPublicPrefix) {
            $URI = preg_replace("%/+%", '/',  $this->getPublicURIPrefix() . $URI);
        }

        return $this->compiledComponentsInfo [ "#" ] [ strtolower($URI) ] ?? NULL;
    }

    /**
     * If the component requires other components, use this method to determine which ones.
     *
     * @param string $componentName
     * @return array|null
     */
    public function getRequirementsForComponent(string $componentName): ?array {
        if(NULL === $this->compiledComponentsInfo) {
            $this->compiledComponentsInfo = require $this->getCompiledComponentsFilename();
        }
        return $this->compiledComponentsInfo [ "@" ] [ $componentName ] ?? NULL;
    }

    /**
     * @return mixed
     */
    public function getPublicURIPrefix()
    {
        return $this->publicURIPrefix;
    }

    /**
     * @return mixed
     */
    public function getResourceDirectory()
    {
        return $this->resourceDirectory;
    }
}