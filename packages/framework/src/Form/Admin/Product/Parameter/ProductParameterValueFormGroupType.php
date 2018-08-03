<?php

namespace Shopsys\FrameworkBundle\Form\Admin\Product\Parameter;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductParameterValueFormGroupType extends CollectionType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'productParemeterValueGroup';
    }
}
