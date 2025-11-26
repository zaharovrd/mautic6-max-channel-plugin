<?php

namespace MauticPlugin\MauticMaxBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;
use MauticPlugin\MauticMaxBundle\DependencyInjection\MauticMaxExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class MauticMaxBundle extends PluginBundleBase
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new MauticMaxExtension();
    }
}
