<?php

declare(strict_types=1);

namespace Symkit\FormBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RichSelectExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [ChoiceType::class];
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['block_prefixes'][] = 'rich_select';

        $view->vars['searchable'] = $options['searchable'];
        $view->vars['choice_icons'] = $options['choice_icons'];
        $view->vars['required'] = $options['required'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'searchable' => true,
            'choice_icons' => [],
        ]);

        $resolver->setAllowedTypes('searchable', 'bool');
        $resolver->setAllowedTypes('choice_icons', 'array');
    }
}
