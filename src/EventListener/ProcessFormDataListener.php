<?php

declare(strict_types=1);

namespace Terminal42\NotificationCenterBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Terminal42\NotificationCenterBundle\MessageType\CoreFormMessageType;
use Terminal42\NotificationCenterBundle\NotificationCenter;

#[AsHook('processFormData')]
class ProcessFormDataListener
{
    public function __construct(private NotificationCenter $notificationCenter)
    {
    }

    public function __invoke(array $submittedData, array $formData, array|null $files, array $labels, Form $form): void
    {
        if (!isset($formData['nc_notification']) || !is_numeric($formData['nc_notification'])) {
            return;
        }

        $rawTokens = [];
        $rawData = [];
        $rawDataFilled = [];

        foreach ($submittedData as $k => $v) {
            $label = $labels[$k] ?? ucfirst($k);

            $rawTokens['formlabel_'.$k] = $label;
            $rawTokens['form_'.$k] = $v;
            $rawData[] = $label.': '.(\is_array($v) ? implode(', ', $v) : $v);

            if (\is_array($v) || \strlen($v)) {
                $rawDataFilled[] = $label.': '.(\is_array($v) ? implode(', ', $v) : $v);
            }
        }

        $rawTokens['raw_data'] = implode("\n", $rawData);
        $rawTokens['raw_data_filled'] = implode("\n", $rawDataFilled);

        $tokens = $this->notificationCenter->createTokenCollectionFromArray($rawTokens, CoreFormMessageType::NAME);
        $this->notificationCenter->sendNotification((int) $formData['nc_notification'], $tokens);
    }
}
