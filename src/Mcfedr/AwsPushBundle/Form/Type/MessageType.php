<?php


namespace Mcfedr\AwsPushBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('text')
            ->add('badge')
            ->add('sound');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Mcfedr\AwsPushBundle\Message\Message'
        ]);
    }

    public function getBlockPrefix()
    {
        return 'message';
    }
}
