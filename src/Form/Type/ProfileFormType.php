<?php

namespace App\Form\Type;

use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ProfileFormType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->remove('current_password');
    }

    public function getName()
    {
        return 'app_user_profile';
    }

    /**
     * Builds the embedded form representing the user.
     */
    protected function buildUserForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'profile.show.username',
                'translation_domain' => 'FOSUserBundle',
            ])
            ->add('email', EmailType::class, ['label' => 'profile.show.email', 'translation_domain' => 'FOSUserBundle'])
            ->add('firstname', TextType::class, ['required' => false, 'label' => 'Prénom'])
            ->add('lastname', TextType::class, ['required' => false, 'label' => 'Nom'])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Écrivez une courte description',
                ],
            ])
            ->add('website', UrlType::class, [
                'required' => false,
                'label' => 'Site Web',
            ])
            ->add('showSocials', CheckboxType::class, [
                'required' => false,
                'label' => 'Afficher un lien vers mes réseaux sociaux',
            ])
            ->add('imageFile', VichImageType::class, [
                'required' => false,
                'label' => 'Photo de profil',
                'thumb_params' => ['w' => 200, 'h' => 200, 'fit' => 'fill'],
            ]);
    }
}
