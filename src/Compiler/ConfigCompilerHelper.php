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


use Skyline\Component\Config\AbstractComponent;
use Skyline\Component\Config\OpenDirectoryComponent;
use TASoft\Config\Compiler\Factory\FactoryInterface;
use TASoft\Config\Compiler\StandardFactoryCompiler;
use TASoft\Config\Config;

class ConfigCompilerHelper extends StandardFactoryCompiler
{
    protected function mergeConfiguration(Config $collected, Config $new)
    {
        $iterator = function(Config $cfg, $parentKey = NULL) use (&$iterator, $collected) {
            foreach($cfg->toArray(false) as $key => $value) {
                if(is_string($key) && $key == AbstractComponent::COMP_REQUIREMENTS) {
                    $cg = $collected["@"];
                    if(!$cg)
                        $collected["@"] = $cg = new Config();
                    $cg[ $parentKey] = $value;
                }
                elseif($value instanceof OpenDirectoryComponent) {
                    $cg = $collected["##"];
                    if(!$cg)
                        $collected["##"] = $cg = new Config();
                    $cg[ $value->getConfig()['uri'] ] = $value->getConfig()["dir"];
                } elseif($value instanceof Config)
                    $cfg[$key] = $iterator($value, $key);
                elseif($value instanceof FactoryInterface) {
                    $conf = $value->toConfig();
                    if(isset($conf[ AbstractComponent::COMP_ELEMENT_ARGUMENTS ]["file"])) {
                        $file = $conf[AbstractComponent::COMP_ELEMENT_ARGUMENTS]["file"];
                        if($tg = $conf[AbstractComponent::COMP_ELEMENT_ARGUMENTS][0] ?? NULL) {
                            $cg = $collected["#"];
                            if(!$cg)
                                $collected["#"] = $cg = new Config();
                            $cg[strtolower($tg)] = $file;
                        }
                    }

                    $cc = $conf[ AbstractComponent::COMP_ELEMENT_ARGUMENTS ];
                    unset($cc["file"]);
                    $conf[ AbstractComponent::COMP_ELEMENT_ARGUMENTS ] = $cc;

                    $cfg[$key] = $iterator($conf, $key);
                }
            }
            return $cfg;
        };
        parent::mergeConfiguration($collected, $iterator($new));
    }
}