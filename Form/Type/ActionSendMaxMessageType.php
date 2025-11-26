<?php

namespace MauticPlugin\MauticMaxBundle\Form\Type;

use Mautic\MarketingMessageBundle\Form\Type\MarketingMessageListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\RouterInterface;

final class ActionSendMaxMessageType extends AbstractType
{
    public function __construct(
        private RouterInterface $router
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'message',
            MarketingMessageListType::class,
            [
                'label'      => 'mautic.max.campaign.send_message',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.max.campaign.send_message.tooltip',
                ],
                'channel'    => 'max', // Фильтруем сообщения только для нашего канала
                'add_button' => [
                    'text' => 'mautic.marketingmessage.list.add_new',
                    'icon' => 'fa fa-plus',
                    'attr' => [
                        'data-toggle'   => 'ajaxmodal',
                        'data-target'   => '#MauticSharedModal',
                        'data-header'   => 'mautic.marketingmessage.new.header',
                        'href'          => $this->router->generate('mautic_marketingmessage_action', [
                            'objectAction' => 'new',
                            'channel'      => 'max',
                        ]),
                    ],
                ]
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        // Должен совпадать с alias из config.php
        return 'max_send_message';
    }
}
