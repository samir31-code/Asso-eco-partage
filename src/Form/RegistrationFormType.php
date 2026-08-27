<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 📧 Identifiants de connexion
            ->add('email', TextType::class, [
                'label' => 'Votre adresse email*',
                'attr' => ['class' => 'form-control', 'placeholder' => 'samirgagster@msn.com']
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'first_options'  => [
                    'label' => 'Votre mot de passe*',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Votre mot de passe*']
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe*',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Confirmer le mot de passe*']
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer un mot de passe.'),
                    new Length(
                        min: 8,
                        minMessage: 'Votre mot de passe doit faire au moins {{ limit }} caractères.',
                        max: 4096,
                    ),
                ],
            ])

            // 👤 Vos informations personnelles
            ->add('nom', TextType::class, [
                'label' => 'Nom*',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom*']
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom*',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Prénom*']
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone portable*',
                'attr' => ['class' => 'form-control', 'placeholder' => '06 12 34 56 78*'],
                'constraints' => [
                    new NotBlank(message: 'Le numéro de téléphone est obligatoire.'),
                    new Regex(
                        pattern: '/^(?:(?:\+|00)33|0)[1-9](?:[\s.-]*\d{2}){4}$/',
                        message: 'Veuillez entrer un numéro de téléphone valide.'
                    )
                ]
            ])

            // 📍 Votre adresse de facturation
            ->add('adresse', TextType::class, [
                'label' => 'Numéro et rue*',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Numéro et rue*']
            ])
            ->add('complementAdresse', TextType::class, [
                'label' => 'Complément d\'adresse',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Complément d\'adresse (Appt, étage...)']
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal*',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Code postal*'],
                'constraints' => [
                    new NotBlank(message: 'Le code postal est requis.'),
                    new Regex(
                        pattern: '/^[0-9]{5}$/',
                        message: 'Le code postal doit contenir 5 chiffres.'
                    )
                ]
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville*',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ville*']
            ])
            ->add('pays', CountryType::class, [
                'label' => 'Pays*',
                'preferred_choices' => ['FR'],
                'attr' => ['class' => 'form-select'],
                'data' => 'FR'
            ])

            // ⚖️ RGPD / Conditions d'utilisation
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'J\'accepte les conditions d\'utilisation d\'Eco-Partage*',
                'attr' => ['class' => 'form-check-input'],
                'constraints' => [
                    new IsTrue(message: 'Vous devez accepter les conditions d\'utilisation.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
