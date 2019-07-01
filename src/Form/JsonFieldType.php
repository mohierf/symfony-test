<?php

namespace App\Form;

use App\Entity\JsonField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JsonFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('jsonSchema')
            ->add('parent')
/*
            ->add('parent', EntityType::class,
                [
                    'class' => JsonField::class,
                    'choice_label' => 'name',
                    'query_builder' => static function (JsonFieldRepository $er) {
                        return $er->createQueryBuilder('e')->orderBy('e.name', 'ASC');
                    },
                ]
            )
 */
            ->add('level')
            ->add('name')
            ->add('type', ChoiceType::class, [
                'help' => 'Mandatory field type',
                'multiple' => false,
                'choices' => [
                    'String' => 'string',
                    'Integer' => 'integer',
                    'Boolean' => 'boolean',
                    'Null' => 'null',
                    'Object' => 'object',
                    'Array' => 'array',
                ],
            ])
            ->add('nullable', CheckboxType::class, [
                'label' => 'Nullable field? The field is present but may be a null type',
                'required' => false,
            ])
            ->add('required', CheckboxType::class, [
                'label' => 'Required field?',
                'required' => false,
            ])
            ->add('format', ChoiceType::class, [
                'help' => 'Field format',
                'multiple' => false,
                'choices' => [
                    'None' => null,
                    'Date and Time' => 'date-time',
                    'Date' => 'date',
                    'Time' => 'time',
                    'Email' => 'email',
                    'Ip address' => 'ipv4',
                    'URITime' => 'uri',
                ],
            ])
            ->add('pattern', TextType::class, [
                'required' => false,
                'help' => 'The pattern must be a valid regular expression.',
                'empty_data' => '',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => JsonField::class,
        ]);
    }
}
