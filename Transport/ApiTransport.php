<?php

namespace MauticPlugin\MauticMaxBundle\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\ChannelBundle\Helper\TransportCallbackResponse;
use Mautic\ChannelBundle\Transport\TransportInterface;
use MauticPlugin\MauticMaxBundle\Integration\Configuration;
use Psr\Log\LoggerInterface;

class ApiTransport implements TransportInterface
{
    private const API_ENDPOINT = 'https://platform-api.max.ru';

    public function __construct(
        private LoggerInterface $logger,
        private Configuration $configuration
    ) {}

    public function send(MessageQueue $messageQueue): TransportCallbackResponse
    {
        if (!$this->configuration->isPublished()) {
            return new TransportCallbackResponse(false, 'MAX integration is disabled.');
        }

        $accessToken = $this->configuration->getAccessToken();
        if (!$accessToken) {
            return new TransportCallbackResponse(false, 'MAX Access Token is not configured.');
        }

        $contact = $messageQueue->getContact();
        // Получаем ID контакта из кастомного поля
        $maxUserId = $contact->getFieldValue('max_user_id');

        if (empty($maxUserId)) {
            $logMessage = sprintf('Contact ID %d does not have a MAX User ID.', $contact->getId());
            $this->logger->warning('MAX: ' . $logMessage);
            return new TransportCallbackResponse(false, $logMessage, null, true); // true = DNC
        }

        $messageDetails = $messageQueue->getMessageDetails();
        $text           = $messageDetails['content'] ?? '';

        $payload = [
            'text' => $text,
            // Сюда можно будет добавить кнопки в будущем
            // 'attachments' => [...]
        ];

        try {
            $client = new Client(['base_uri' => self::API_ENDPOINT]);

            $response = $client->post('messages', [
                'query' => [
                    'user_id'      => $maxUserId,
                    'access_token' => $accessToken,
                ],
                'json' => $payload,
                'timeout' => 10,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $messageId = $body['message']['body']['mid'] ?? null;
            $this->logger->debug(sprintf('MAX: Message sent to contact %d (MAX ID: %s)', $contact->getId(), $maxUserId));

            return new TransportCallbackResponse(true, 'Sent', ['message_id' => $messageId]);
        } catch (RequestException $e) {
            $errorMessage = $e->hasResponse()
                ? (string) $e->getResponse()->getBody()
                : $e->getMessage();
            $this->logger->error(sprintf('MAX: API error for contact %d: %s', $contact->getId(), $errorMessage));

            return new TransportCallbackResponse(false, 'API Error: ' . $errorMessage);
        }
    }

    /**
     * Mautic требует этот метод, но для MAX он не используется.
     */
    public function getMessage(string $messageId, array $config)
    {
        return null;
    }
}
