<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(message: 'Veuillez renseigner votre prénom.'),
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Veuillez renseigner votre nom.'),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse (numéro et rue)',
                'required' => false,
            ])
            ->add('complementAdresse', TextType::class, [
                'label' => 'Complément d\'adresse',
                'required' => false,
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code Postal',
                'required' => false,
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'required' => false,
            ])
            ->add('pays', TextType::class, [
                'label' => 'Pays',
                'required' => false,
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
