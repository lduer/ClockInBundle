<?php

/*
 * This file is part of the ClockInBundle for Kimai 2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\ClockInBundle\Form;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Timesheet;
use KimaiPlugin\ClockInBundle\Form\Type\HiddenEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form for setting the clock-in buttons.
 */
class ClockInForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('project', HiddenEntityType::class, [
                'class' => Project::class,
                'label' => 'label.project',
            ])
            ->add('activity', HiddenEntityType::class, [
                'class' => Activity::class
            ])
            ->add('description', TextareaType::class, [
                'label' => 'label.description',
                'attr' => [
                    'placeholder' => 'label.description'
                ],
                'required' => false
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'action.save',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Timesheet::class,
            'csrf_protection' => false
        ]);
    }
}
