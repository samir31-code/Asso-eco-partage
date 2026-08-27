<?php

namespace App\Form;

use App\Entity\Outil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class OutilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('description')
            ->add('categorie')
            ->add('etat')
            ->add('image', FileType::class, [
                'label' => 'Photos de l\'outil (Plusieurs fichiers possibles)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    // 🌟 On passe directement le tableau de contraintes à "All" sans la clé interne 'constraints'
                    new All([
                        new File(
                            maxSize: '2M',
                            mimeTypes: [
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ],
                            mimeTypesMessage: 'Veuillez uploader un format d\'image valide (JPEG, PNG, WEBP).',
                            maxSizeMessage: 'L\'image est trop lourde (2 Mo maximum).'
                        )
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
                    'multiple' => 'multiple'
                ]
            ])
            // 🛠️ TON CHAMP FLEXIBLE TYPE WORDPRESS
            ->add('optionsTexte', TextareaType::class, [
                'label' => 'Caractéristiques personnalisées (une par ligne)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'placeholder' => "Marque: Bosch\nPuissance: 1200W\nPoids: 4.5kg",
                    'rows' => 4,
                    'class' => 'form-control'
                ],
                'help' => 'Écrivez sous la forme "Clé: Valeur", par exemple "Marque: Makita". Mettez une seule caractéristique par ligne.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Outil::class,
        ]);
    }
}
