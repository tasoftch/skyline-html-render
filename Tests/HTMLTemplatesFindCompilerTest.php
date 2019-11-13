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

/**
 * HTMLTemplatesFindCompilerTest.php
 * Skyline HTML Render
 *
 * Created on 2019-07-06 18:42 by thomas
 */

use PHPUnit\Framework\TestCase;
use Skyline\HTMLRender\Template\Loader\LayoutFileLoader;
use Skyline\HTMLRender\Template\Loader\PhtmlFileLoader;
use Skyline\Render\Compiler\Template\MutableTemplate;
use Skyline\Render\Specification\Container;

class HTMLTemplatesFindCompilerTest extends TestCase
{
    public function testDefaultPhtmlLoader() {
        $loader = new PhtmlFileLoader(__DIR__ . "/Examples/simple-phtml-test.phtml");
        $template = $loader->loadTemplate();

        $this->assertEquals("Meine Seite", $template->getName());
        $this->assertEquals(["main", "content", "etwas"], $template->getTags());

        $this->assertEquals("Aussagekräftiger Titel", $template->getAttribute( PhtmlFileLoader::ATTR_TITLE ));
        $this->assertEquals("Hehe meine erste Homepage", $template->getAttribute( PhtmlFileLoader::ATTR_DESCRIPTION ));

        $this->assertEquals([
            "Application",
            "API"
        ], $template->getAttribute( PhtmlFileLoader::ATTR_REQUIRED_COMPONENTS ));

        $this->assertEquals([
            "jQuery"
        ], $template->getAttribute( PhtmlFileLoader::ATTR_OPTIONAL_COMPONENTS ));

        $this->assertEquals([
            "og:uri" => 'my-test-uri@tawoft.ch'
        ], $template->getAttribute(PhtmlFileLoader::ATTR_META));
    }

    public function testLayoutSubTemplateLoader() {
        $loader = new DebugLayoutFileLoader(__DIR__ . "/Examples/simple-templates.phtml");
        $template = $loader->loadTemplate();

        $templates = $template->getAttribute(LayoutFileLoader::ATTR_TEMPLATES);

        $this->assertEquals([
            "#" => "mein-template"
        ], $templates["Navigation"]);

        $this->assertEquals([
            "name" => "test-template"
        ], $templates["Intro"]);

        $this->assertEquals([
            "category" => "Kategorie"
        ], $templates["Test"]);

        $this->assertEquals([
            "category" => "Kategorie",
            "name" => 'Vorlage-Name'
        ], $templates["Other"]);

        $this->assertEquals([
            "name" => "my-template",
            "tags" => [
                "tag1",
                "tag2",
                "tag3"
            ]
        ], $templates["Tagged"]);

        $loader = new LayoutFileLoader(__DIR__ . "/Examples/simple-templates.phtml");
        $template = $loader->loadTemplate();

        $templates = $template->getAttribute(LayoutFileLoader::ATTR_TEMPLATES);
        print_r($templates);

        $this->assertInstanceOf(Container::class, $templates["Navigation"]);
    }

    public function testMoreComplexTemplates() {
        $loader = new DebugLayoutFileLoader(__DIR__ . "/Examples/complex-templates.phtml");
        $template = $loader->loadTemplate();

        $templates = $template->getAttribute(LayoutFileLoader::ATTR_TEMPLATES);

        $this->assertEquals([
            "tags" => [
                "tag1",
                "tag2",
                "tag3"
            ]
        ], $templates["Navigation"]);

        $this->assertEquals([
            "category" => 'MyCat',
            "tags" => [
                "tag1",
                "tag2",
                "tag3"
            ]
        ], $templates["Cate"]);
    }
}

class DebugLayoutFileLoader extends LayoutFileLoader {
    protected function compilePredefinedTemplate($name, $templateInfo, MutableTemplate $template) {
        $predefinedTemplates = $template->getAttribute( self::ATTR_TEMPLATES );
        $predefinedTemplates[$name] = $templateInfo;
        $template->setAttribute(self::ATTR_TEMPLATES, $predefinedTemplates);
    }
}
