<?php

namespace App\Form;

use App\Entity\Farm;
use App\Entity\Veterinarian;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FarmType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nome',
                'attr' => ['placeholder' => 'Nome da fazenda'],
            ])
            ->add('size', NumberType::class, [
                'label' => 'Tamanho (HA)',
                'attr' => ['placeholder' => 'Tamanho em hectares'],
                'invalid_message' => 'Informe um valor numérico válido para o tamanho.',
            ])
            ->add('manager', TextType::class, [
                'label' => 'Responsável',
                'attr' => ['placeholder' => 'Nome do responsável'],
            ])
            ->add('veterinarians', EntityType::class, [
                'class' => Veterinarian::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Veterinários',
                'attr' => ['class' => 'form-select', 'size' => 5],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Farm::class,
        ]);
    }
}
