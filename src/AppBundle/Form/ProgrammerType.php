<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProgrammerType extends AbstractType // This is a programmer form.
{
    public function buildForm(FormBuilderInterface $builder, array $options) // building the form and its fields
    {
        $builder // Using the builder object to create different fields on the form and passing them different options
            ->add('nickname', 'text', [
                // if we're in edit mode, then the nickname feild will be disabled
                'disabled' => $options['is_edit'] // referencing the option 'is_edit'
            ])
            ->add('avatarNumber', 'choice', [
                'choices' => [
                    // the key is the value that will be set
                    // the value/label isn't shown in an API, and could
                    // be set to anything e.g. "1" is the key and the rest after the "=>" is the value/label
                    1 => 'Girl (green)',
                    2 => 'Boy',
                    3 => 'Cat',
                    4 => 'Boy with Hat',
                    5 => 'Happy Robot',
                    6 => 'Girl (purple)',
                ]
            ])
            ->add('tagLine', 'textarea') // Setting the field tagLine to a textarea so that is how it appears on the form
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Programmer', // binding this form to the Programmer entity object
            'is_edit' => false, // Creating/Changing an option called 'is_edit' and setting its default value to false
        ));
    }

    public function getName()
    {
        return 'programmer';
    }
}