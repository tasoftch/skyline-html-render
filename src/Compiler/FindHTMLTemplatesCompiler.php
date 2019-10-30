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

namespace Skyline\HTMLRender\Compiler;

use Skyline\Compiler\Context\Code\SourceFile;
use Skyline\HTMLRender\Template\Loader\LayoutFileLoader;
use Skyline\HTMLRender\Template\Loader\PhtmlFileLoader;
use Skyline\HTMLRender\Template\Loader\ViewFileLoader;
use Skyline\Render\Compiler\FindTemplatesCompiler;
use Skyline\Render\Compiler\Template\MutableTemplate;
use Skyline\Render\Template\Loader\LoaderInterface;

class FindHTMLTemplatesCompiler extends FindTemplatesCompiler
{
    /**
     * @inheritDoc
     */
    protected function getLoaderForFile(SourceFile $sourceFile): LoaderInterface
    {
        if(preg_match($this->getTemplateFilenamePattern(), $sourceFile, $ms)) {
            switch (strlen($ms[0])) {
                case 13: // .layout.phtml
                    return new LayoutFileLoader($sourceFile);
                case 11: // .view.phtml
                    return new ViewFileLoader($sourceFile);
                default:
            }
        }
        return new PhtmlFileLoader($sourceFile);
    }

    /**
     * @inheritDoc
     */
    protected function adjustLoadedTemplate(MutableTemplate $template, SourceFile $sourceFile): MutableTemplate
    {

        return parent::adjustLoadedTemplate($template, $sourceFile);
    }

    /**
     * @inheritDoc
     */
    public function getTemplateFilenamePattern(): string
    {
        return "/\.layout\.phtml|\.view\.phtml|\.phtml$/i";
    }
}