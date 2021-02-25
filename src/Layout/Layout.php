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

namespace Skyline\HTMLRender\Layout;


use Skyline\HTMLRender\Template\Loader\PhtmlFileLoader;
use Skyline\Render\Context\DefaultRenderContext;
use Skyline\Render\Context\RenderContextInterface;
use Skyline\Render\Info\RenderInfoInterface;
use Skyline\Render\Model\ExtractableModelInterface;
use Skyline\Render\Template\Extension\ExtendableAwareTemplateInterface;
use Skyline\Render\Template\Extension\TemplateExtensionTrait;
use Skyline\Render\Template\FileTemplate;
use Skyline\Render\Template\Nested\NestableTemplateInterface;
use Skyline\Render\Template\Nested\TemplateNestingTrait;
use TASoft\Util\PathTool;

/**
 * The abstrat layout defines
 * @package Skyline\HTMLRender
 */
class Layout extends FileTemplate implements ExtendableAwareTemplateInterface, NestableTemplateInterface
{
    use TemplateExtensionTrait;
    use TemplateNestingTrait;

    private $didLoadExtensions = false;

    /**
     * @inheritDoc
     */
    public function getRenderable(): callable
    {
        $FILE = $this->getFilename();
        return function($list) use ($FILE) {
            if($list instanceof LayoutVariableList) {
                foreach ($list as $key => $value) {
                    $$key = $value;
                }
                unset($list);
            } elseif (is_array($list))
                extract($list);

            $self = $this;
            if($self instanceof DefaultRenderContext) {
                if($model = $self->getRenderInfo()->get( RenderInfoInterface::INFO_MODEL )) {
                    if($model instanceof ExtractableModelInterface) {
                        foreach($model->getKeys() as $key) {
                            if(!preg_match("/^[_a-z][a-z0-9_]*$/i", $key))
                                $theKey = $model->getKeyForInvalidKey($key);
                            else
                                $theKey = $key;

                            $$theKey = $model->getValueForKey( $key );
                        }
                    }
                    unset($model, $theKey, $key);
                }
            }
            unset($self);

			if(PathTool::isZeroPath( $FILE ))
				require $FILE;
			else
				require SkyGetRoot() . $FILE;
        };
    }

    /**
     * @inheritDoc
     */
    public function getRequiredExtensionIdentifiers(): array
    {
        if(!$this->didLoadExtensions)
            return $this->getAttribute( PhtmlFileLoader::ATTR_REQUIRED_COMPONENTS ) ?: [];
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getOptionalExtensionIdentifiers(): array
    {
        if(!$this->didLoadExtensions)
            return $this->getAttribute( PhtmlFileLoader::ATTR_OPTIONAL_COMPONENTS ) ?: [];
        return [];
    }

    /**
     * @return bool
     */
    public function didLoadExtensions(): bool
    {
        return $this->didLoadExtensions;
    }

    /**
     * @param bool $didLoadExtensions
     */
    public function setDidLoadExtensions(bool $didLoadExtensions): void
    {
        $this->didLoadExtensions = $didLoadExtensions;
    }
}