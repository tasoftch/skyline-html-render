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

namespace Skyline\HTMLRender\Template;


use Skyline\Render\Context\DefaultRenderContext;
use Skyline\Render\Context\RenderContextInterface;
use TASoft\Service\ServiceManager;

abstract class AbstractDirectRenderTemplate implements DirectRenderTemplateInterface
{
    private $additionalInfo;


    public function getID()
    {
        return uniqid();
    }

    public function getName(): string
    {
        return "Built-In Direct Render Template";
    }

    public function getRenderable(): callable
    {
        $self = $this;
        return function($additionalInfo) use ($self) {
            ((function() use ($additionalInfo, $self){$this->additionalInfo=$additionalInfo;})->bindTo($self, get_class($self)))();
            return (string) $self;
        };
    }

    /**
     * Call this method inside the string convert to get eventually passed additional information
     *
     * @return mixed
     * @see DefaultRenderContext::renderSubTemplate()
     */
    protected function getAdditionalInfo() {
        return $this->additionalInfo;
    }

    /**
     * Call this method inside the string convert to obtain the render context instance
     *
     * @return RenderContextInterface|null
     */
    protected function getCurrentContext(): ?RenderContextInterface {
        if($ctx = ServiceManager::generalServiceManager()->get("renderContext")) {
            /** @var RenderContextInterface $ctx */
            return $ctx;
        }
        return NULL;
    }
}