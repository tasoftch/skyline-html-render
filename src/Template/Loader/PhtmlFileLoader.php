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


use Skyline\Render\Compiler\Template\MutableTemplate;
use Skyline\Render\Template\Loader\TemplateFileLoader;

class PhtmlFileLoader extends TemplateFileLoader
{
    const ATTR_TITLE = 'title';
    const ATTR_DESCRIPTION = 'description';
    const ATTR_REQUIRED_COMPONENTS = 'require';
    const ATTR_OPTIONAL_COMPONENTS = 'optional';

    /** @var string Meta are specific tags for opengraph (og:title, og:icon, ...) use @meta icon ..., @meta title ... */
    const ATTR_META = 'meta';

    /**
     * @inheritDoc
     */
    protected function loadIntoMutable(MutableTemplate $template): void
    {
        $name = explode(".", basename( $this->getFilename()) , 2)[0];
        $template->setName($name);

        $tokens = token_get_all( file_get_contents($this->getFilename()) );
        foreach($tokens as $token) {
            if (is_array($token) && $token[0] ?? T_DOC_COMMENT) {
                if ($this->parseDocComment($token[1], $template))
                    break;
            }
        }
        if(!$template->getCatalogName()) {
            $template->setCatalogName( basename(dirname($template->getId())) );
        }
    }

    /**
     * Should parse a doc comment inside the template file into template metadata.
     * Returning true will stop looking for further doc comments.
     *
     * @param string $docComment
     * @param MutableTemplate $template
     * @return bool
     */
    protected function parseDocComment(string $docComment, MutableTemplate $template): bool {
        if(preg_match_all("/^\s*\*\s*@([a-z0-9_\-]+)\s*(.*?)$/im", $docComment, $ms)) {
            for($e=0;$e<count($ms[1]);$e++) {
                $this->mapAnnotation($ms[1][$e], trim($ms[2][$e]), $template);
            }
            return true;
        }
        return false;
    }

    /**
     * Called by parseDocComment to map an annotation into the template
     *
     * @param string $annotationName
     * @param string $annotationValue
     * @param MutableTemplate $template
     */
    protected function mapAnnotation(string $annotationName, string $annotationValue, MutableTemplate $template) {
        switch (strtolower($annotationName)) {
            case self::ATTR_TITLE:
            case self::ATTR_DESCRIPTION:
                $template->setAttribute(strtolower($annotationName), $annotationValue);
                break;
            case 'tag':
                $template->addTag($annotationValue);
                break;
            case 'name':
                $template->setName($annotationValue);
                break;
            case 'catalog':
                $template->setCatalogName($annotationValue);
                break;
            case self::ATTR_OPTIONAL_COMPONENTS:
            case self::ATTR_REQUIRED_COMPONENTS:
                $template->setAttribute(strtolower($annotationName), $annotationValue, true);
                break;
            case 'meta':
                if(preg_match("/^\s*([a-z\-:]+)\s+(.+)$/i", $annotationValue, $ms)) {
                    $metas = $template->getAttribute( self::ATTR_META );
                    if(!$metas)
                        $metas = [];

                    $metas[ $ms[1] ] = $ms[2];

                    $template->setAttribute( self::ATTR_META , $metas);
                }
                break;
            default:
                $this->mapUnknownAnnotation($annotationName, $annotationValue, $template);
        }
    }

    /**
     * Can be used by subclasses to register further annotations
     *
     * @param string $annotationName
     * @param string $annotationValue
     * @param MutableTemplate $template
     */
    protected function mapUnknownAnnotation(string $annotationName, string $annotationValue, MutableTemplate $template) {
    }
}