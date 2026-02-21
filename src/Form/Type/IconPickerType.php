<?php

declare(strict_types=1);

namespace Symkit\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symkit\FormBundle\Service\HeroiconProvider;

final class IconPickerType extends AbstractType
{
    public function __construct(
        private readonly HeroiconProvider $heroiconProvider,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $icons = $this->heroiconProvider->getAllIcons();

        $resolver->setDefaults([
            'choices' => array_flip($icons),
            'choice_icons' => array_combine(array_keys($icons), array_keys($icons)),
            'placeholder' => 'Select an icon...',
            'searchable' => true,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
