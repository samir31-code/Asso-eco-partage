<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', ChoiceType::class, [
                'label' => 'Sujet',
                'choices' => [
                    'Abonnement / Commande / Facturation' => 'abonnement',
                    'Support technique' => 'technique',
                    'Autre demande' => 'autre',
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [new NotBlank()],
            ])
            ->add('accountNumber', TextType::class, [
                'label' => 'N° de compte client (Facultatif)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Retrouvez-le en vous connectant à votre compte client dans "Vos informations"'
                ],
            ])
            ->add('fullName', TextType::class, [
                'label' => 'Votre prénom et nom',
                'attr' => ['class' => 'form-control'],
                'constraints' => [new NotBlank()],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre adresse email',
                'attr' => ['class' => 'form-control'],
                'constraints' => [new NotBlank()],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Votre message',
                'attr' => ['class' => 'form-control', 'rows' => 6],
                'constraints' => [new NotBlank()],
            ])
            ->add('consent', CheckboxType::class, [
                'label' => 'J\'autorise la Maison écologique à utiliser ces données en respect de sa politique de confidentialité.',
                'mapped' => false,
                'constraints' => [
                    // Correction ici : on passe le message directement ou via un tableau indexé, plus de tableau de configuration global
                    new IsTrue(message: 'Vous devez accepter la politique de confidentialité.')
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}