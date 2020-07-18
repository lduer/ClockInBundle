<?php

/*
 * This file is part of the ClockInBundle for Kimai 2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\ClockInBundle\Form;

use KimaiPlugin\ClockInBundle\ClockIn\ClockInService;
use KimaiPlugin\ClockInBundle\Entity\LatestActivity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form for setting the clock-in buttons.
 */
class ClockInButtonForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!isset($options['data'])) {
            $disabledButtonStates = ClockInService::getButtonDisabledStates();
        } else {
            /** @var LatestActivity $latestActivity */
            $latestActivity = $options['data'];

            $disabledButtonStates = ClockInService::getButtonDisabledStates($latestActivity);
        }

        $builder
            ->add('start', SubmitType::class, [
                'label' => 'label.start',
                'disabled' => $disabledButtonStates['start']
            ]);

        $builder
            ->add('pause', SubmitType::class, [
                'label' => 'label.pause',
                'disabled' => $disabledButtonStates['pause']
            ]);

        $builder
            ->add('resume', SubmitType::class, [
                'label' => 'label.resume',
                'disabled' => $disabledButtonStates['resume']
            ]);

        $builder
            ->add('activity', SubmitType::class, [
                'label' => 'label.activity',
                'translation_domain' => 'messages'
            ]);

        $builder
            ->add('stop', SubmitType::class, [
                'label' => 'label.stop',
                'disabled' => $disabledButtonStates['stop']
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'translation_domain' => 'clock-in'
        ]);
    }
}
