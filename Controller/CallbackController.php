<?php

namespace MauticPlugin\MauticMaxBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Контроллер для приема входящих вебхуков от MAX API.
 */
class CallbackController extends CommonController
{
    /**
     * Обрабатывает входящий вебхук.
     */
    public function processAction(Request $request): Response
    {
        $logger = $this->container->get('monolog.logger.mautic');
        $payload = json_decode($request->getContent(), true);

        // Проверяем, что получили валидный JSON и что это событие от MAX
        if (json_last_error() !== JSON_ERROR_NONE || !isset($payload['update_type'])) {
            $logger->warning('MAX Callback: Received invalid payload.', ['content' => $request->getContent()]);

            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        }

        $logger->debug('MAX Callback: Received payload.', $payload);

        try {
            // Передаем данные в сервис-обработчик для дальнейшей логики
            $callbackHandler = $this->container->get('mautic.max.transport.callback');
            $callbackHandler->process($payload);
        } catch (\Exception $e) {
            $logger->error('MAX Callback: Error processing update.', ['exception' => $e]);
            // Возвращаем ошибку сервера, но MAX может попытаться отправить вебхук снова
            return new Response('Error processing update', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Отправляем ответ 200 OK, чтобы MAX понял, что вебхук успешно получен
        return new Response('OK', Response::HTTP_OK);
    }
}
