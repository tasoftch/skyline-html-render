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

namespace Skyline\HTMLRender\Helper;


use Skyline\HTMLRender\HTMLRenderController;
use Skyline\HTMLRender\Template\Loader\PhtmlFileLoader;
use Skyline\Kernel\Service\SkylineServiceManager;
use Skyline\Render\Template\AdvancedTemplateInterface;
use Skyline\Render\Template\Extension\ExtendableTemplateInterface;
use Skyline\Render\Template\Extension\TemplateExtensionInterface;
use Skyline\Render\Template\Nested\NestableTemplateInterface;
use Skyline\Render\Template\TemplateInterface;
use TASoft\Collection\Element\DependencyCollectonElement;
use TASoft\Collection\Exception\DuplicatedObjectException;

class ComponentResolverHelper
{
    /** @var ExtendableTemplateInterface */
    private $template;

    /** @var TemplateInterface[] */
    private $subTemplates;

    /**
     * ComponentResolverHelper constructor.
     * @param ExtendableTemplateInterface $template
     * @param TemplateInterface $subTemplates
     */
    public function __construct(ExtendableTemplateInterface $template, $subTemplates)
    {
        $this->template = $template;
        $this->subTemplates = $subTemplates;
    }

    /**
     * @return ExtendableTemplateInterface
     */
    public function getTemplate(): ExtendableTemplateInterface
    {
        return $this->template;
    }

    /**
     * @return TemplateInterface[]
     */
    public function getSubTemplates()
    {
        return $this->subTemplates;
    }

    /**
     * @param $template
     * @param $attributeName
     * @return \Generator
     */
    public static function iterateOverAttributes($template, $attributeName) {
        if($template instanceof AdvancedTemplateInterface) {
            if($template instanceof NestableTemplateInterface) {
                foreach($template->getNestedTemplates() as $temp) {
                    yield from self::iterateOverAttributes($temp, $attributeName);
                }
            }

            $attr = $template->getAttribute( $attributeName );
            if(is_array($attr)) {
                foreach($attr as $k => $a)
                    yield $k => $a;
            } elseif($attr)
                yield $attr;
        }
    }

    public function resolve() {
        /** @var HTMLRenderController $rc */
        $rc = SkylineServiceManager::getServiceManager()->get("renderController");

        $templates = array_merge([ $this->getTemplate() ], $this->getSubTemplates() ?: []);

        $required = new AdditionalDependencyCollection(function($requiredName) use ($rc) {
            if($rc->hasComponent($requiredName)) {
                $requirements = $rc->getRequirementsForComponent($requiredName);
                if($requirements)
                    return new DependencyCollectonElement($requiredName, $requirements, $requiredName);
                return new DependencyCollectonElement($requiredName, [], $requiredName);
            }
            return NULL;
        });



        $optional = new AdditionalDependencyCollection(function($requiredName) use ($rc, $required) {
            if($required->contains($requiredName))
                return NULL;
            if($rc->hasComponent($requiredName)) {
                $requirements = $rc->getRequirementsForComponent($requiredName);
                if($requirements)
                    return new DependencyCollectonElement($requiredName, $requirements, $requiredName);
                return new DependencyCollectonElement($requiredName, [], $requiredName);
            }
            return NULL;
        });

        foreach($templates as $template) {
            foreach(self::iterateOverAttributes($template, PhtmlFileLoader::ATTR_REQUIRED_COMPONENTS) as $componentName) {
                $requirements = $rc->getRequirementsForComponent($componentName);
                try {
                    if($requirements)
                        $required->add($componentName, $componentName, $requirements);
                    else
                        $required->add($componentName, $componentName);
                } catch (DuplicatedObjectException $exception) {
                    // Just ignore it
                }
            }

            foreach(self::iterateOverAttributes($template, PhtmlFileLoader::ATTR_OPTIONAL_COMPONENTS) as $componentName) {
                $requirements = $rc->getRequirementsForComponent($componentName);
                try {
                    if($requirements)
                        $optional->add($componentName, $componentName, $requirements);
                    else
                        $optional->add($componentName, $componentName);
                } catch (DuplicatedObjectException $exception) {
                    // Just ignore it
                }
            }
        }

        $template = $this->getTemplate();

        if(count($required)) {
            $ms = microtime(true);
            foreach($required->getOrderedElements() as $req) {
                $elements = $rc->getComponentElements( $req );
                foreach ($elements as $key => $element) {
                    if($element instanceof TemplateExtensionInterface) {
                        $template->registerExtension($element, "$req.$key");
                    }
                }
            }
        }

        if(count($optional)) {
            foreach($optional->getOrderedElements() as $req) {
                $elements = $rc->getComponentElements( $req );
                foreach ($elements as $key => $element) {
                    if($element instanceof TemplateExtensionInterface) {
                        $template->registerExtension($element, "$req.$key");
                    }
                }
            }
        }
    }
}