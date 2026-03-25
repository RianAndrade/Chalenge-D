<?php

namespace App\Form;

use App\Entity\Cow;
use App\Entity\Farm;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Código',
                'attr' => ['placeholder' => 'Código da cabeça de gado'],
            ])
            ->add('milk', NumberType::class, [
                'label' => 'Leite (litros/semana)',
                'attr' => ['placeholder' => 'Litros de leite por semana', 'min' => 0, 'max' => 10000, 'step' => '0.01'],
                'invalid_message' => 'Informe um valor numérico válido.',
            ])
            ->add('feed', NumberType::class, [
                'label' => 'Ração (kg/semana)',
                'attr' => ['placeholder' => 'Quilos de ração por semana', 'min' => 0.01, 'max' => 10000, 'step' => '0.01'],
                'invalid_message' => 'Informe um valor numérico válido.',
            ])
            ->add('weight', NumberType::class, [
                'label' => 'Peso (kg)',
                'attr' => ['placeholder' => 'Peso do animal em quilos', 'min' => 0.01, 'max' => 5000, 'step' => '0.01'],
                'invalid_message' => 'Informe um valor numérico válido.',
            ])
            ->add('birthdate', DateType::class, [
                'label' => 'Data de Nascimento',
                'widget' => 'single_text',
            ])
            ->add('farm', EntityType::class, [
                'class' => Farm::class,
                'choice_label' => 'name',
                'label' => 'Fazenda',
                'placeholder' => 'Selecione uma fazenda',
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cow::class,
        ]);
    }
}
