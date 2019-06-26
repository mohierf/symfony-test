<?php

namespace App\Form;

use App\Entity\JsonSchema;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class JsonSchemaType.
 */
class JsonSchemaType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'required'   => true,
                'label' => 'trans.field.name',
                'empty_data' => 'NewSchema',
            ])
            ->add('content', TextareaType::class, [
                'required'   => false,
                'label' => 'trans.field.content',
                'empty_data' => '{
                    "description": "New Schema",
                    "type": "object",
                    "required": [
                    ],
                    "properties": {
                        "id": {
                            "type": "string"
                        },
                        "name": {
                            "type": "string"
                        },
                        "number": {
                            "type": "integer"
                        },
                        "date": {
                            "type": "string",
                            "format": "date-time"
                        },
                    }
                }',
                'attr' => [
                    'rows' => 20,
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => JsonSchema::class,
            ]
        );
    }
}
