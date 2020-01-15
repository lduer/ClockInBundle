<?php

/*
 * This file is part of the ClockInBundle for Kimai 2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\ClockInBundle\Form;

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
            $disableStop = true;
            $disableStart = false;
            $disablePause = true;
            $disableResume = true;
        } else {
            /** @var LatestActivity $latestActivity */
            $latestActivity = $options['data'];

            $disableStop = (in_array($latestActivity->getAction(), [LatestActivity::ACTIVITY_STOP, LatestActivity::ACTIVITY_PAUSE]));
            $disableStart = (in_array($latestActivity->getAction(), [null, LatestActivity::ACTIVITY_START, LatestActivity::ACTIVITY_RESUME]));
            $disablePause = (in_array($latestActivity->getAction(), [LatestActivity::ACTIVITY_PAUSE, LatestActivity::ACTIVITY_STOP]));
            $disableResume = (in_array($latestActivity->getAction(), [null, LatestActivity::ACTIVITY_RESUME, LatestActivity::ACTIVITY_STOP]));
        }

        $builder
            ->add('start', SubmitType::class, [
                'label' => 'label.start',
                'disabled' => $disableStart
            ]);

        // active only when clocked in.
        $builder
            ->add('pause', SubmitType::class, [
                'label' => 'label.pause',
                'disabled' => $disablePause
            ]);

        // active only, when paused
        $builder
            ->add('resume', SubmitType::class, [
                'label' => 'label.resume',
                'disabled' => $disableResume
            ]);

        // always active
        $builder
            ->add('activity', SubmitType::class, [
                'label' => 'label.activity',
                'translation_domain' => 'messages'
            ]);

        // active only when clocked in
        $builder
            ->add('stop', SubmitType::class, [
                'label' => 'label.stop',
                'disabled' => $disableStop
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
