<?php

namespace App\Form;

use App\Entity\Card;
use App\Entity\Emv3DS;
use App\Entity\Transaction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class   TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('transOrder')
            ->add('transType')
            ->add('currency')
            ->add('emv3DS', EntityType::class, [
                'class' => Emv3DS::class,
                'choice_label' => 'id',
            ])
            ->add('card', EntityType::class, [
                'class' => Card::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}
