<?php

namespace MauticPlugin\MauticMaxBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\MarketingMessageBundle\Model\MarketingMessageModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticMaxBundle\Form\Type\ActionSendMaxMessageType;
use MauticPlugin\MauticMaxBundle\Integration\Configuration;
use MauticPlugin\MauticMaxBundle\Transport\ApiTransport;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CampaignSubscriber implements EventSubscriberInterface
{
    public const ACTION_KEY   = 'max.send_message';
    public const EVENT_NAME   = 'plugin.max.send_message';

    public function __construct(
        private Configuration $configuration,
        private ApiTransport $transport,
        private MarketingMessageModel $marketingMessageModel,
        private AuditLogModel $auditLogModel,
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPLAIN_ON_BUILD => ['onCampaignBuild', 0],
            self::EVENT_NAME                  => ['onExecuteAction', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        // Добавляем действие только если плагин включен и настроен
        if (!$this->configuration->isPublished()) {
            return;
        }

        $event->addAction(self::ACTION_KEY, [
            'label'          => 'mautic.max.action.send_message.label',
            'description'    => 'mautic.max.action.send_message.desc',
            'formType'       => ActionSendMaxMessageType::class,
            'eventName'      => self::EVENT_NAME,
            'channel'        => 'max',
            'channelIdField' => 'message',
        ]);
    }

    public function onExecuteAction(CampaignExecutionEvent $event): void
    {
        $contact = $event->getContact();
        if (!$contact) {
            $event->setFailed('No contact in context');
            return;
        }

        $config = $event->getConfig();
        $messageId = $config['message'] ?? null;

        if (!$messageId) {
            $event->setFailed('Marketing Message not selected');
            return;
        }

        $marketingMessage = $this->marketingMessageModel->getEntity($messageId);
        if (!$marketingMessage) {
            $event->setFailed(sprintf('Marketing Message with ID %d not found', $messageId));
            return;
        }

        // Создаем очередь сообщений Mautic
        $messageQueue = new MessageQueue();
        $messageQueue->setContact($contact);
        $messageQueue->setChannel('max');
        $messageQueue->setChannelId($marketingMessage->getId());

        // Mautic автоматически подставит токены {contact.field}
        $content = $marketingMessage->getTranslation($contact->getPreferredLocale(), true)->getContent();

        $messageQueue->setMessageDetails(
            [
                'content' => $content,
                // Здесь в будущем можно будет добавлять кнопки
                // 'buttons' => [...]
            ]
        );

        $this->logger->debug(
            sprintf('MAX: Queuing message ID %d for contact ID %d', $messageId, $contact->getId())
        );

        // Отправляем сообщение через наш транспорт
        $response = $this->transport->send($messageQueue);

        if ($response->isSuccessful()) {
            $this->auditLogModel->writeToLog(
                // ...
            );
            $event->setResult(true);
        } else {
            $this->logger->error(
                sprintf('MAX: Failed to send message to contact %d: %s', $contact->getId(), $response->getFailedReason())
            );
            $event->setResult(false);
            $event->setFailed($response->getFailedReason());
        }
    }
}
