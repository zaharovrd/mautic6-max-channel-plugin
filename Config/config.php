<?php

return [
    'name'        => 'MAX Channel',
    'description' => 'Adds MAX Bot API as a communication channel for Mautic campaigns.',
    'version'     => '1.0.0',
    'author'      => 'MautiBox',

    // ===== Меню (Каналы > MAX Messages) =====
    'menu' => [
        'main' => [
            'mautic.channel.max' => [ // Уникальный ключ для нашего пункта меню
                'id'        => 'mautic_channel_max',
                'iconClass' => 'fa-comments', // Иконка, похожая на мессенджер
                'label'     => 'mautic.max.channel.messages', // Ключ для перевода, будет выглядеть как "MAX Messages"
                'parent'    => 'mautic.core.channels',
                'route'     => 'mautic_marketingmessage_index', // Используем СТАНДАРТНЫЙ роут Mautic
                'path'      => '/marketing-messages/max', // Добавляем сегмент, чтобы отфильтровать по нашему каналу
            ],
        ],
    ],

    // ===== Регистрация канала в Mautic =====
    'channels' => [
        'max' => [
            'label'                => 'MAX',
            'transport'            => 'mautic.max.transport.api',
            'callback_transport'   => 'mautic.max.transport.callback',
            'message_settings'     => [
                'supports_text_message'     => true,
                'supports_asset_attachment' => true,
                'supports_button_message'   => true,
            ],
        ],
    ],

    // ===== Регистрация интеграции (для настроек API) =====
    'integrations' => [
        'mautic.integration.max' => [
            'class' => \MauticPlugin\MauticMaxBundle\Integration\MaxIntegration::class,
            'arguments' => [
                'event_dispatcher',
                'mautic.helper.cache_storage',
                'doctrine.orm.entity_manager',
                'session',
                'request_stack',
                'router',
                'translator',
                'logger',
                'mautic.helper.encryption',
                'mautic.lead.model.lead',
                'mautic.lead.model.company',
                'mautic.helper.paths',
                'mautic.core.model.notification',
                'mautic.lead.model.field',
                'mautic.plugin.model.integration_entity',
                'mautic.lead.model.dnc',
            ],
        ],
    ],

    // ===== Маршруты (Роуты) =====
    'routes' => [
        'main' => [
            // Этот роут нужен, чтобы Mautic правильно построил ссылку в меню.
            // Он использует стандартный контроллер, но мы изменяем path.
            'mautic_marketingmessage_index' => [
                'path'       => '/marketing-messages/{channel}',
                'controller' => 'Mautic\MarketingMessageBundle\Controller\MessageController::indexAction',
                'defaults'   => [
                    'channel' => 'max', // Указываем наш канал по умолчанию для этого роута
                ],
            ],
        ],
        'public' => [
            // Публичный роут для приема Webhook от MAX API
            'mautic_max_callback' => [
                'path'       => '/max/callback',
                'controller' => 'MauticMaxBundle:Callback:process',
            ],
        ],
    ],

    // ===== Подписка на события Mautic =====
    'events' => [
        'mautic.campaign.on_build' => [
            'class'    => \MauticPlugin\MauticMaxBundle\EventListener\CampaignSubscriber::class,
            'method'   => 'onCampaignBuild',
        ],
    ],

    // ===== Сервисы (контейнер зависимостей) =====
    'services' => [
        'events' => [
            'mautic.max.campaign.subscriber' => [
                'class'     => \MauticPlugin\MauticMaxBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.max.integration.configuration',
                ],
            ],
        ],
        'forms' => [
            'mautic.max.form.type.action_send_max_message' => [
                'class' => \MauticPlugin\MauticMaxBundle\Form\Type\ActionSendMaxMessageType::class,
                'alias' => 'max_send_message',
            ],
        ],
        'other' => [
            'mautic.max.integration.configuration' => [
                'class'     => \MauticPlugin\MauticMaxBundle\Integration\Configuration::class,
                'arguments' => [
                    'mautic.helper.integration',
                ],
            ],
            'mautic.max.transport.api' => [
                'class'     => \MauticPlugin\MauticMaxBundle\Transport\ApiTransport::class,
                'arguments' => [
                    'logger',
                    'mautic.max.integration.configuration',
                ],
            ],
            'mautic.max.transport.callback' => [
                'class' => \MauticPlugin\MauticMaxBundle\Callback\MaxCallback::class,
                'arguments' => [
                    'logger',
                    'mautic.lead.model.lead',
                    'mautic.core.model.auditlog',
                ],
            ],
        ],
    ],
];
