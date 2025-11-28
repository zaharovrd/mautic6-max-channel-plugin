<?php

namespace MauticPlugin\MauticMaxBundle\Integration;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MaxIntegration extends AbstractIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;

    public function getName(): string
    {
        return 'Max';
    }

    public function getDisplayName(): string
    {
        return 'MAX';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticMaxBundle/Assets/img/max.png';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType(): string
    {
        return 'api_key';
    }

    /**
     * Определяет поля формы на странице настроек интеграции.
     * 
     * @param FormBuilder $builder
     * @param mixed[]     $data
     * @param string      $formArea
     */
    public function appendToForm(&$builder, $data, $formArea): void
    {
        if ('features' === $formArea) {
            // Поле для ввода токена
            $builder->add(
                'api_keys[access_token]',
                TextType::class,
                [
                    'label' => 'mautic.max.config.form.access_token',
                    'attr'  => [
                        'class'        => 'form-control',
                        'tooltip'      => 'mautic.max.config.form.access_token.tooltip',
                        'data-show-on' => '{"integration_details[published]": "1"}',
                    ],
                    'required' => true,
                ]
            );

            // Отображение URL для вебхука, который нужно скопировать в настройки бота MAX
            $webhookUrl = $this->router->generate('mautic_max_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $builder->add(
                'webhook_url',
                TextType::class,
                [
                    'label' => 'mautic.max.config.form.webhook_url',
                    'attr'  => [
                        'class'    => 'form-control',
                        'readonly' => true,
                        'value'    => $webhookUrl,
                        'tooltip'  => 'mautic.max.config.form.webhook_url.tooltip',
                        'data-show-on' => '{"integration_details[published]": "1"}',
                    ],
                    'disabled' => true,
                    'mapped'   => false, // Это поле не сохраняется в БД
                ]
            );
        }
    }
}
