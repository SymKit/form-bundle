<?php

declare(strict_types=1);

namespace Symkit\FormBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symkit\FormBundle\Form\Extension\CheckboxRichSelectExtension;

final class CheckboxRichSelectExtensionTest extends TypeTestCase
{
    protected function getTypeExtensions(): array
    {
        return [
            new CheckboxRichSelectExtension(),
        ];
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertSame([CheckboxType::class], CheckboxRichSelectExtension::getExtendedTypes());
    }

    public function testBuildView(): void
    {
        $form = $this->factory->create(CheckboxType::class);
        $view = $form->createView();

        $this->assertContains('rich_checkbox', $view->vars['block_prefixes']);
        $this->assertSame(['Yes' => 1, 'No' => 0], $view->vars['choices_data']);
        $this->assertArrayHasKey(1, $view->vars['choice_icons']);
        $this->assertArrayHasKey(0, $view->vars['choice_icons']);
        $this->assertFalse($view->vars['searchable']);
        $this->assertSame('form.component.checkbox_rich_select.placeholder', $view->vars['placeholder']);
    }
}
