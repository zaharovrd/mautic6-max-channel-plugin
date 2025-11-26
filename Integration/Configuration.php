<?php

namespace MauticPlugin\MauticMaxBundle\Integration;

use Mautic\PluginBundle\Helper\IntegrationHelper;

class Configuration
{
    private ?MaxIntegration $integration = null;

    public function __construct(
        private IntegrationHelper $integrationHelper
    ) {}

    private function getIntegrationObject(): ?MaxIntegration
    {
        if (null === $this->integration) {
            $this->integration = $this->integrationHelper->getIntegrationObject('Max');
        }

        return $this->integration;
    }

    public function isPublished(): bool
    {
        $integration = $this->getIntegrationObject();

        return $integration && $integration->getIntegrationSettings()->getIsPublished();
    }

    public function getAccessToken(): ?string
    {
        if (!$this->isPublished()) {
            return null;
        }

        $keys = $this->getIntegrationObject()->getDecryptedApiKeys();

        return $keys['access_token'] ?? null;
    }
}
