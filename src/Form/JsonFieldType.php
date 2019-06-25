<?php

namespace App\Form;

use App\Entity\JsonField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JsonFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('jsonSchema')
            ->add('name')
            ->add('type', ChoiceType::class, [
                'multiple' => false,
                'choices'  => [
                    'String' => 'string',
                    'Integer' => 'integer',
                    'Boolean' => 'boolean',
                    'Object' => 'object',
                    'Array' => 'array',
                ],
            ])
            ->add('required', CheckboxType::class, [
                'label'    => 'Required field?',
                'required' => false,
            ])
            ->add('format')
            ->add('parent')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => JsonField::class,
        ]);
    }
}
