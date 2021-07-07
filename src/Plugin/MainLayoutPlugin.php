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


use Skyline\HTML\Head\Description;
use Skyline\HTML\Head\Meta;
use Skyline\HTML\Head\Title;
use Skyline\HTMLRender\Exception\HTMLRenderException;
use Skyline\HTMLRender\Helper\ComponentResolverHelper;
use Skyline\HTMLRender\HTMLRenderController;
use Skyline\HTMLRender\Layout\Layout;
use Skyline\HTMLRender\Template\Loader\LayoutFileLoader;
use Skyline\HTMLRender\Template\Loader\PhtmlFileLoader;
use Skyline\Kernel\Service\SkylineServiceManager;
use Skyline\Render\Context\DefaultRenderContext;
use Skyline\Render\Event\InternRenderEvent;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Render\Plugin\RenderPluginInterface;
use Skyline\Render\Plugin\RenderTemplateDispatchPlugin;
use Skyline\Render\Specification\Container;
use Skyline\Render\Template\Extension\ExtendableTemplateInterface;
use Skyline\Render\Template\Nested\NestableAwareTemplateInterface;
use Skyline\Translation\TranslationManager;
use TASoft\EventManager\EventManagerInterface;
use TASoft\Service\ServiceManager;

class MainLayoutPlugin implements RenderPluginInterface
{
public function initialize(EventManagerInterface $eventManager)
{
    $eventManager->addListener(RenderTemplateDispatchPlugin::EVENT_HEADER_RENDER, [$this, "resolveTemplateAwareChildren"], 80);
    $eventManager->addListener(RenderTemplateDispatchPlugin::EVENT_HEADER_RENDER, [$this, "collectHTMLComponents"], 90);
    $eventManager->addListener(RenderTemplateDispatchPlugin::EVENT_HEADER_RENDER, [$this, "openPage"], 95);

    $eventManager->addListener(RenderTemplateDispatchPlugin::EVENT_HEADER_RENDER, [$this, "openBody"], 10000);


    $eventManager->addListener(RenderTemplateDispatchPlugin::EVENT_FOOTER_RENDER, [$this, "closePage"], 100);
}

public function tearDown()
{
}



public function openPage(string $eventName, InternRenderEvent $event, $eventManager, ...$arguments) {
$language = '';

if(ServiceManager::generalServiceManager()->serviceExists("translationManager")) {
    $tm = ServiceManager::generalServiceManager()->get("translationManager");
    if($tm instanceof TranslationManager) {
        $language = $tm->getDefaultLocale()->getLanguage();
    }
}
?><!DOCTYPE html>
<html lang="<?=$language?>">
<head>
    <meta charset="UTF-8" />
    <meta name="generator" content="Skyline CMS by TASoft Applications" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <?php

    if($metas = $event->getInfo()->get( RenderInfoInterface::INFO_DYNAMIC_META )) {
        foreach ($metas as $name => $value) {
            printf("\t<meta property=\"%s\" content=\"%s\"/>\n", htmlspecialchars($name), htmlspecialchars($value));
        }
    }

    if($links = $event->getInfo()->get( RenderInfoInterface::INFO_DYNAMIC_LINKS )) {
        foreach ($links as $link) {
            $attrs = [];
            foreach($link as $name => $value)
                $attrs[] = sprintf("%s=\"%s\"", htmlspecialchars($name), htmlspecialchars($value));
            echo "\t<link ", implode(" ", $attrs), "/>\n";
        }
    }

    }

    public function openBody(string $eventName, InternRenderEvent $event, $eventManager, ...$arguments) {
    ?></head>
<body><?php
}

public function closePage(string $eventName, InternRenderEvent $event, $eventManager, ...$arguments) {
?>
</body>
</html><?php
}

public function resolveTemplateAwareChildren(string $eventName, InternRenderEvent $event, $eventManager, ...$arguments) {
    $template = $event->getInfo()->get(RenderInfoInterface::INFO_TEMPLATE);
    $children = $event->getInfo()->get(RenderInfoInterface::INFO_SUB_TEMPLATES);

    if(method_exists($template, 'getAttribute')) {
        if($predefinedTemplates = $template->getAttribute( LayoutFileLoader::ATTR_TEMPLATES )) {
            $update = false;

            $ctx = ServiceManager::generalServiceManager()->get("renderContext");

            if($ctx instanceof DefaultRenderContext) {
                foreach($predefinedTemplates as $name => $info) {
                    if(!isset($children[$name]) && $info instanceof Container) {
                        $update = true;

                        $children[$name] = function() use ($info, $ctx) {
                            if($templates = $ctx->findTemplate($info))
                                return array_shift($templates);
                            return NULL;
                        };
                    }
                }
            }


            if($update)
                $event->getInfo()->set( RenderInfoInterface::INFO_SUB_TEMPLATES, $children );
        }
    }

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
    if($template instanceof ExtendableTemplateInterface) {
        $title = NULL;
        $description = NULL;

        $rc = SkylineServiceManager::getServiceManager()->get("renderController");
        if($rc instanceof HTMLRenderController) {
            $templateStack = $event->getInfo()->get( RenderInfoInterface::INFO_SUB_TEMPLATES );

            $titleIterator = function($template) use (&$title) {
                foreach(ComponentResolverHelper::iterateOverAttributes($template, PhtmlFileLoader::ATTR_TITLE) as $t) {
                    $title = $t;
                    break;
                }
            };

            $descriptionIterator = function($template) use (&$description) {
                foreach(ComponentResolverHelper::iterateOverAttributes($template, PhtmlFileLoader::ATTR_DESCRIPTION) as $t) {
                    $description = $t;
                    break;
                }
            };

            $metaData = [];
            $ogIterator = function($template) use (&$metaData) {
                foreach(ComponentResolverHelper::iterateOverAttributes($template, PhtmlFileLoader::ATTR_META) as $k => $t) {
                    $metaData[$k] = $t;
                }
            };

            $title = $event->getInfo()->get( RenderInfoInterface::INFO_TITLE );
            $description = $event->getInfo()->get( RenderInfoInterface::INFO_DESCRIPTION );



            foreach ($templateStack as $tpl) {
                if(!$title)
                    $titleIterator($tpl);
                if(!$description)
                    $descriptionIterator($tpl);

                $ogIterator($tpl);
            }

            if(!$title) {
                $titleIterator($template);
            }
            if(!$description) {
                $descriptionIterator($template);
            }

            $ogIterator($template);



            $ogTitle = false;
            $ogDesc = false;

            if($metaData) {
                foreach($metaData as $name => $content) {
                    $name = strtolower($name);
                    if($name == 'title')
                        $ogTitle = true;
                    if($name == 'description')
                        $ogDesc = true;
                    $template->registerExtension(new Meta("$name", $content), "$name");
                }
            }

            if($title) {
                $template->registerExtension(new Title($title), 'title');
                if(!$ogTitle)
                    $template->registerExtension(new Meta("og:title", $title, true), 'og:title');
            }

            if($description) {
                $template->registerExtension(new Description($description), 'description');
                if(!$ogDesc)
                    $template->registerExtension(new Meta("og:description", $description, true), 'og:description');
            }


            $helper = new ComponentResolverHelper($template, $templateStack);
            $helper->resolve();


            if($template instanceof Layout)
                $template->setDidLoadExtensions(true);
        }
    }
}
}