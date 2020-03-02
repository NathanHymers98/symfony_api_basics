<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UpdateProgrammerType extends ProgrammerType // This forms function is exactly the same as the programmer form, except it has a different default value for the field option 'is_edit'
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        // override this!
        $resolver->setDefaults(['is_edit' => true]); // In this update form, the default value for the option is_edit is set to true, meaning that the field nickname will be disabled
    }

    public function getName()
    {
        return 'programmer_edit';
    }
}