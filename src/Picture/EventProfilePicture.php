<?php

namespace App\Picture;

use App\Entity\Event;
use App\Twig\AssetExtension;
use Symfony\Component\Asset\Packages;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class EventProfilePicture
{
    /** @var UploaderHelper
     */
    private $helper;

    /** @var Packages
     */
    private $packages;

    /** @var AssetExtension */
    private $assetExtension;

    public function __construct(UploaderHelper $helper, Packages $packages, AssetExtension $assetExtension)
    {
        $this->assetExtension = $assetExtension;
        $this->helper = $helper;
        $this->packages = $packages;
    }

    public function getOriginalPicture(Event $event)
    {
        if ($event->getPath()) {
            return $this->packages->getUrl(
                $this->helper->asset($event, 'file'),
                'aws'
            );
        }

        if ($event->getSystemPath()) {
            return $this->packages->getUrl(
                $this->helper->asset($event, 'systemFile'),
                'aws'
            );
        }

        if ($event->getUrl()) {
            return $event->getUrl();
        }

        return $this->packages->getUrl(
            AssetExtension::ASSET_PREFIX . '/images/empty_event.png',
        );
    }

    public function getPicture(Event $event, array $params = [])
    {
        if ($event->getPath()) {
            return $this->assetExtension->thumb($this->helper->asset($event, 'file'), $params);
        }

        if ($event->getSystemPath()) {
            return $this->assetExtension->thumb($this->helper->asset($event, 'systemFile'), $params);
        }

        return $this->assetExtension->thumbAsset(
            $this->packages->getUrl(AssetExtension::ASSET_PREFIX . '/images/empty_event.png', 'local'),
            $params
        );
    }
}
