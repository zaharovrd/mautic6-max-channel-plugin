<?php

namespace MauticPlugin\MauticMaxBundle\Callback;

use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Model\LeadModel;
use Psr\Log\LoggerInterface;

class MaxCallback
{
    public function __construct(
        private LoggerInterface $logger,
        private LeadModel $leadModel,
        private AuditLogModel $auditLogModel
    ) {}

    /**
     * Обрабатывает полезную нагрузку от вебхука MAX.
     */
    public function process(array $update): void
    {
        switch ($update['update_type'] ?? null) {
            case 'message_created':
                $this->handleIncomingMessage($update);
                break;
            case 'message_callback':
                $this->handleCallback($update);
                break;
            default:
                $this->logger->debug('MAX Callback: Received unhandled update_type.', $update);
        }
    }

    /**
     * Обрабатывает входящее сообщение от пользователя.
     */
    private function handleIncomingMessage(array $update): void
    {
        $userId = $update['message']['sender']['user_id'] ?? null;
        $text   = $update['message']['body']['text'] ?? '[no text]';

        if (!$userId) {
            $this->logger->warning('MAX Callback: message_created without user_id.', $update);
            return;
        }

        $contact = $this->findContactByMaxId($userId);
        if (!$contact) {
            $this->logger->info(sprintf('MAX Callback: Received a message from an unknown MAX user ID: %s', $userId));
            // Здесь можно добавить логику создания нового контакта
            return;
        }

        $this->auditLogModel->writeToLog([
            'bundle'    => 'MauticMaxBundle',
            'object'    => 'message',
            'objectId'  => $contact->getId(),
            'action'    => 'message_reply',
            'details'   => ['text' => $text, 'raw' => $update],
            'ipAddress' => 'MAX API',
        ]);
    }

    /**
     * Обрабатывает нажатие на callback-кнопку.
     */
    private function handleCallback(array $update): void
    {
        $userId  = $update['callback']['user']['user_id'] ?? null;
        $payload = $update['callback']['payload'] ?? null;

        if (!$userId || !$payload) {
            $this->logger->warning('MAX Callback: message_callback with missing data.', $update);
            return;
        }

        $contact = $this->findContactByMaxId($userId);
        if (!$contact) {
            return; // Логирование уже произошло в findContactByMaxId
        }

        $this->auditLogModel->writeToLog([
            'bundle'    => 'MauticMaxBundle',
            'object'    => 'button',
            'objectId'  => $contact->getId(),
            'action'    => 'button_click',
            'details'   => ['payload' => $payload, 'raw' => $update],
            'ipAddress' => 'MAX API',
        ]);
    }

    private function findContactByMaxId(int $userId)
    {
        return $this->leadModel->getRepository()->findOneBy(['max_user_id' => $userId]);
    }
}
