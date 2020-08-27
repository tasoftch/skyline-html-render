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

use Skyline\Compiler\Factory\AbstractExtendedCompilerFactory;
use Skyline\Compiler\Predef\ConfigurationCompiler;
use Skyline\HTMLRender\Compiler\ComponentsConfigurationCompiler;
use Skyline\HTMLRender\Compiler\FindHTMLTemplatesCompiler;

return [
    'components-config' => [
        AbstractExtendedCompilerFactory::COMPILER_CLASS_KEY                            => ComponentsConfigurationCompiler::class,
        ConfigurationCompiler::INFO_TARGET_FILENAME_KEY     => 'components.config.php',
        ConfigurationCompiler::INFO_PATTERN_KEY             => '/^components\.cfg\.php$/i',
        ConfigurationCompiler::INFO_CUSTOM_FILENAME_KEY     => [
        	'components.config.php',
			'components.ui.config.php'
		],
        AbstractExtendedCompilerFactory::COMPILER_DEPENDENCIES_KEY => [
            'composer-packages-order'
        ]
    ],
    "find-html-templates" => [
        AbstractExtendedCompilerFactory::COMPILER_CLASS_KEY => FindHTMLTemplatesCompiler::class,
        AbstractExtendedCompilerFactory::COMPILER_DEPENDENCIES_KEY => [
            'components-config',
            "find-templates"
        ]
    ]
];
