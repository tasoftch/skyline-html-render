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

namespace Skyline\HTMLRender\Template\Loader;


use Skyline\HTMLRender\Layout\Layout;
use Skyline\Render\Compiler\Template\MutableTemplate;
use TASoft\Parser\SimpleTokenParser;
use TASoft\Parser\Token\TokenInterface;
use TASoft\Parser\Tokenizer\Adaptor\ExtendedNamesAdaptor;
use TASoft\Parser\Tokenizer\Filter\WhitespaceFilter;
use TASoft\Parser\Tokenizer\PhpExpressionBasedTokenizer;

class LayoutFileLoader extends PhtmlFileLoader
{
    const ATTR_TEMPLATES = 'templates';

    /**
     * @inheritDoc
     */
    protected function loadIntoMutable(MutableTemplate $template): void
    {
        parent::loadIntoMutable($template);
        if(!$template->getCatalogName())
            $template->setCatalogName("Layouts");
    }

    /**
     * @inheritDoc
     */
    protected function parseDocComment(string $docComment, MutableTemplate $template): bool
    {
        $template->setTemplateClassName( Layout::class );
        return parent::parseDocComment($docComment, $template);
    }

    /**
     * @inheritDoc
     */
    protected function mapUnknownAnnotation(string $annotationName, string $annotationValue, MutableTemplate $template)
    {
        switch (strtolower($annotationName)) {
            case 'template':
                $p = new SimpleTokenParser( $ns = new ExtendedNamesAdaptor( new PhpExpressionBasedTokenizer(), '-.' ) );
                $ns->setFilters( [ new WhitespaceFilter() ] );

                /** @var TokenInterface[] $tokens */
                $tokens = $p->parseString($annotationValue);

                $predefinedTemplates = $template->getAttribute( self::ATTR_TEMPLATES );
                if(!$predefinedTemplates)
                    $predefinedTemplates = [];
                // Annotation scheme: @template Name <definition>
                // Where name is the called name inside a layout eg: RenderContext::renderSubTemplate('Name');

                // The <definition> describes the template notation to look up:
                // "#templateID"            => a specific template by its identifier
                // "templateName"           => the first found template name
                // "Category/"              => The first template in category
                // "Category/templateName"  => The first template named templateName in category Category
                // "(tag1, tag2, tag3)"     => The first template containing ALL tags
                // "Category/(tag1, tag2)"

                $name = array_shift($tokens)->getContent();

                $parseTags = function() use (&$tokens, &$predefinedTemplates, $name) {
                    /** @var TokenInterface[] $tokens */
                    if(($tk = reset($tokens)) && $tk->getContent() == '(') {
                        while ($next = array_shift($tokens)) {
                            if($next->getCode() == T_STRING)
                                $predefinedTemplates[$name]["tags"][] = $next->getContent();
                            elseif($next->getContent() == ')') {
                                break;
                            }
                        }
                    }
                };

                $parseTags();

                $next = array_shift($tokens);


                if($next) {
                    if($next->getCode() == T_COMMENT && $next->getContent()[0] == '#') {
                        $predefinedTemplates[$name]["#"] = substr($next->getContent(), 1);
                    } elseif($next->getCode() == T_STRING) {
                        // Can be name or category
                        $string = $next->getContent();
                        $next = array_shift($tokens);
                        if(!$next)
                            $predefinedTemplates[$name]["name"] = $string;
                        else {
                            if($next->getContent() == '/') {
                                // Category
                                $predefinedTemplates[$name]["category"] = $string;
                                $next = array_shift($tokens);
                                if($next && $next->getCode() == T_STRING) {
                                    $predefinedTemplates[$name]["name"] = $next->getContent();
                                } elseif($next)
                                    array_unshift($tokens, $next);
                            } else {
                                $predefinedTemplates[$name]["name"] = $string;
                                array_unshift($tokens, $next);
                            }

                            $parseTags();
                        }
                    }
                }
                $template->setAttribute(self::ATTR_TEMPLATES, $predefinedTemplates);
            default:
        }
    }
}