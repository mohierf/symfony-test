<?php

namespace App\Form;

use App\Entity\Command;
use App\Entity\Host;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('host_name', TextType::class, [
                'required'=> true,
            ])
            ->add('display_name', TextType::class, [
                'required'=> false,
            ])
            ->add('register', CheckboxType::class, [
                'label' => 'Check for a real object, else it will be considered as a template',
                'required' => false,
            ])
            ->add('address', TextType::class, [
                'required'=> false,
                'empty_data' => '',
            ])
            ->add('check_interval')
            ->add('check_command', EntityType::class, [
                'class' => Command::class,
                'choice_label' => 'name',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Host::class,
        ]);
    }
}
