<?php

declare(strict_types=1);

namespace Terminal42\NotificationCenterBundle\EventListener\Backend\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Security\Core\Security;
use Terminal42\NotificationCenterBundle\Backend\AutoSuggester;
use Terminal42\NotificationCenterBundle\Config\ConfigLoader;
use Twig\Environment;

class MessageListener
{
    public function __construct(private AutoSuggester $autoSuggester, private ConfigLoader $configLoader, private ContaoFramework $framework, private Connection $connection, private Security $security, private Environment $twig)
    {
    }

    #[AsCallback(table: 'tl_nc_message', target: 'list.sorting.child_record')]
    public function onChildRecordCallback(array $row): string
    {
        if (null === ($message = $this->configLoader->loadMessage((int) $row['id']))) {
            return '';
        }

        $gateway = $this->configLoader->loadGateway($message->getGateway());
        $languageNames = Languages::getNames($this->security->getUser()?->language ?? null);

        $query = $this->connection->createQueryBuilder()
            ->select('id, language')
            ->from('tl_nc_language')
            ->where('pid = :pid')
            ->setParameter('pid', $message->getId())
        ;

        $languagesFormatted = [];

        foreach ($query->fetchAllAssociative() as $language) {
            $languagesFormatted[] = [
                'name' => $languageNames[$language['language']],
            ];
        }

        return $this->twig->render('@Terminal42NotificationCenter/message.html.twig', [
            'message' => $message,
            'gateway' => $gateway,
            'languages' => $languagesFormatted,
        ]);
    }

    #[AsCallback(table: 'tl_nc_message', target: 'config.onload')]
    public function onLoadCallback(DataContainer $dc): void
    {
        if (
            null === ($message = $this->configLoader->loadMessage((int) $dc->id))
            || null === ($gateway = $this->configLoader->loadGateway($message->getGateway()))
        ) {
            return;
        }

        if (isset($GLOBALS['TL_DCA']['tl_nc_message']['palettes'][$gateway->getType()])) {
            $GLOBALS['TL_DCA']['tl_nc_message']['palettes']['default'] = $GLOBALS['TL_DCA']['tl_nc_message']['palettes'][$gateway->getType()];
        }

        if (
            null !== ($notification = $this->configLoader->loadNotification($message->getNotification()))
            && ($type = $notification->getType())
        ) {
            $this->autoSuggester->enableAutoSuggesterOnDca('tl_nc_message', $type);
        }
    }

    /**
     * @return array<string>
     */
    #[AsCallback(table: 'tl_nc_message', target: 'fields.email_template.options')]
    public function onTokenTransformerOptionsCallback(): array
    {
        return $this->framework->getAdapter(Controller::class)->getTemplateGroup('mail_');
    }
}
