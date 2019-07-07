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

namespace Skyline\HTMLRender\Plugin;


use Skyline\HTMLRender\Exception\HTMLRenderException;
use Skyline\HTMLRender\Template\Loader\PhtmlFileLoader;
use Skyline\Render\Event\InternRenderEvent;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Render\Plugin\RenderPluginInterface;
use Skyline\Render\Plugin\RenderTemplateDispatchPlugin;
use Skyline\Render\Template\AdvancedTemplateInterface;
use Skyline\Render\Template\Nested\NestableAwareTemplateInterface;
use Skyline\Render\Template\Nested\NestableTemplateInterface;
use TASoft\EventManager\EventManagerInterface;

class MainLayoutPlugin implements RenderPluginInterface
{
    public function initialize(EventManagerInterface $eventManager)
    {
        $eventManager->addListener(RenderTemplateDispatchPlugin::EVENT_HEADER_RENDER, [$this, "collectHTMLComponents"], 90);
        $eventManager->addListener(RenderTemplateDispatchPlugin::EVENT_HEADER_RENDER, [$this, "resolveTemplateAwareChildren"], 80);

        $eventManager->addListener(RenderTemplateDispatchPlugin::EVENT_HEADER_RENDER, [$this, "openPage"], 95);
        $eventManager->addListener(RenderTemplateDispatchPlugin::EVENT_FOOTER_RENDER, [$this, "closePage"], 100);
    }

    public function tearDown()
    {
    }

    public function openPage(string $eventName, InternRenderEvent $event, $eventManager, ...$arguments) {
        ?><!DOCTYPE html>
<html lang="">
    <head>
        <meta name="generator" content="Skyline CMS by TASoft Applications" />
        <?php
    }

    public function closePage(string $eventName, InternRenderEvent $event, $eventManager, ...$arguments) {
        ?>
    </head>
</html><?php
    }

    public function resolveTemplateAwareChildren(string $eventName, InternRenderEvent $event, $eventManager, ...$arguments) {
        $template = $event->getInfo()->get(RenderInfoInterface::INFO_TEMPLATE);
        $children = $event->getInfo()->get(RenderInfoInterface::INFO_SUB_TEMPLATES);

        if($template instanceof NestableAwareTemplateInterface) {
            foreach($template->getRequiredIdentifiers() as $id) {
                $child = $children[$id] ?? NULL;
                if(!$child) {
                    $e = new HTMLRenderException("Required subtemplate missing");
                    $e->setSubTemplateName( $id );
                    throw $e;
                }
                $template->registerTemplate($child, $id);
            }

            foreach($template->getOptionalIdentifiers() as $id) {
                $child = $children[$id] ?? NULL;
                if($child) {
                    $template->registerTemplate($child, $id);
                }
            }
        }
    }

    public function collectHTMLComponents(string $eventName, InternRenderEvent $event, $eventManager, ...$arguments)
    {
        $template = $event->getInfo()->get(RenderInfoInterface::INFO_TEMPLATE);

        $iterateOverAttributes = function($template, $attributeName) use (&$iterateOverAttributes) {
            if($template instanceof AdvancedTemplateInterface) {
                if($template instanceof NestableTemplateInterface) {
                    foreach($template->getNestedTemplates() as $temp) {
                        yield from $iterateOverAttributes($temp, $attributeName);
                    }
                }

                $attr = $template->getAttribute( $attributeName );
                if($attr)
                    yield $attr;
            }
        };

        $title = NULL;
        $description = NULL;
        foreach($iterateOverAttributes($template, PhtmlFileLoader::ATTR_TITLE) as $title)
            break;
        foreach($iterateOverAttributes($template, PhtmlFileLoader::ATTR_DESCRIPTION) as $description)
            break;
        $required = iterator_to_array( $iterateOverAttributes($template, PhtmlFileLoader::ATTR_REQUIRED_COMPONENTS) );
        $optional = iterator_to_array( $iterateOverAttributes($template, PhtmlFileLoader::ATTR_OPTIONAL_COMPONENTS) );

        if($required = array_unique($required)) {

        }
    }
}