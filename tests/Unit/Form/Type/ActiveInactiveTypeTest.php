<?php

declare(strict_types=1);

namespace Symkit\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symkit\FormBundle\Form\Type\ActiveInactiveType;

final class ActiveInactiveTypeTest extends TypeTestCase
{
    public function testGetParent(): void
    {
        $type = new ActiveInactiveType();
        $this->assertSame(ChoiceType::class, $type->getParent());
    }

    public function testDefaultOptions(): void
    {
        $form = $this->factory->create(ActiveInactiveType::class);
        $options = $form->getConfig()->getOptions();

        $this->assertSame('form.type.active_inactive.label', $options['label']);
        $this->assertEquals(['form.type.active_inactive.choice.active' => true, 'form.type.active_inactive.choice.inactive' => false], $options['choices']);
        $this->assertFalse($options['searchable']);
        $this->assertArrayHasKey(true, $options['choice_icons']);
        $this->assertArrayHasKey(false, $options['choice_icons']);
    }

    public function testSubmitValidData(): void
    {
        $form = $this->factory->create(ActiveInactiveType::class);

        // choice value in symfony form for TRUE is typically '1' and FALSE is '0'
        $form->submit('1');
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->getData());

        $form = $this->factory->create(ActiveInactiveType::class);
        $form->submit('0');
        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->getData());
    }
}
