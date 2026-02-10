<?php

namespace AwardWallet\MobileBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToArrayTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToTimestampTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;

class CreditCardExpirationType extends DateType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateFormat = self::DEFAULT_FORMAT;
        $timeFormat = \IntlDateFormatter::NONE;
        $calendar = \IntlDateFormatter::GREGORIAN;
        $pattern = is_string($options['format']) ? $options['format'] : null;

        if (null !== $pattern && (false === strpos($pattern, 'y') || false === strpos($pattern, 'M'))) {
            throw new InvalidOptionsException(sprintf('The "format" option should contain the letters "y" and "M". Its current value is "%s".', $pattern));
        }

        if ('single_text' === $options['widget']) {
            $builder->addViewTransformer(new DateTimeToLocalizedStringTransformer(
                $options['model_timezone'],
                $options['view_timezone'],
                $dateFormat,
                $timeFormat,
                $calendar,
                $pattern
            ));
        } else {
            $yearOptions = $monthOptions = [
                'error_bubbling' => false,
                'label_attr' => [
                    'data-error-label' => false,
                ],
            ];

            $formatter = new \IntlDateFormatter(
                \Locale::getDefault(),
                $dateFormat,
                $timeFormat,
                'UTC',
                $calendar,
                $pattern
            );
            $formatter->setLenient(false);

            if ('choice' === $options['widget']) {
                // Only pass a subset of the options to children
                $yearOptions['choices'] = array_flip($this->formatTimestamps($formatter, '/y+/', $this->listYears($options['years'])));
                $yearOptions['placeholder'] = $options['placeholder']['year'];
                $monthOptions['choices'] = array_flip($this->formatTimestamps($formatter, '/M+/', $this->listMonths($options['months'])));
                $monthOptions['placeholder'] = $options['placeholder']['month'];
            }

            // Append generic carry-along options
            foreach (['required', 'translation_domain'] as $passOpt) {
                $yearOptions[$passOpt] = $monthOptions[$passOpt] = $options[$passOpt];
            }

            $builder
                ->add('year', $options['widget'], $yearOptions)
                ->add('month', $options['widget'], $monthOptions)
                ->addViewTransformer(new DateTimeToArrayTransformer(
                    $options['model_timezone'], $options['view_timezone'], ['year', 'month']
                ))
                ->setAttribute('formatter', $formatter)
            ;
        }

        if ('string' === $options['input']) {
            $builder->addModelTransformer(new ReversedTransformer(
                new DateTimeToStringTransformer($options['model_timezone'], $options['model_timezone'], 'Y-m')
            ));
        } elseif ('timestamp' === $options['input']) {
            $builder->addModelTransformer(new ReversedTransformer(
                new DateTimeToTimestampTransformer($options['model_timezone'], $options['model_timezone'])
            ));
        } elseif ('array' === $options['input']) {
            $builder->addModelTransformer(new ReversedTransformer(
                new DateTimeToArrayTransformer($options['model_timezone'], $options['model_timezone'], ['year', 'month'])
            ));
        }
    }

    public function getBlockPrefix()
    {
        return 'cc_expiration';
    }

    private function formatTimestamps(\IntlDateFormatter $formatter, $regex, array $timestamps)
    {
        $pattern = $formatter->getPattern();
        $timezone = $formatter->getTimezoneId();

        $formatter->setTimezoneId(\DateTimeZone::UTC);

        if (preg_match($regex, $pattern, $matches)) {
            $formatter->setPattern($matches[0]);

            foreach ($timestamps as $key => $timestamp) {
                $timestamps[$key] = $formatter->format($timestamp);
            }

            // I'd like to clone the formatter above, but then we get a
            // segmentation fault, so let's restore the old state instead
            $formatter->setPattern($pattern);
        }

        $formatter->setTimezoneId($timezone);

        return $timestamps;
    }

    private function listYears(array $years)
    {
        $result = [];

        foreach ($years as $year) {
            $result[$year] = gmmktime(0, 0, 0, 6, 15, $year);
        }

        return $result;
    }

    private function listMonths(array $months)
    {
        $result = [];

        foreach ($months as $month) {
            $result[$month] = gmmktime(0, 0, 0, $month, 15);
        }

        return $result;
    }
}
