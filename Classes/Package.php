<?php

namespace DIU\MetaData\AutoTag;

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use DIU\MetaData\AutoTag\Mapper\AssetTagMapper;
use Neos\Media\Domain\Service\AssetService;
use Neos\MetaData\MetaDataManager;

class Package extends BasePackage
{
    /**
     * @inheritdoc
     *
     * @param Bootstrap $bootstrap The current bootstrap
     *
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(AssetService::class, 'assetCreated', AssetTagMapper::class, 'mapMetaData');

    }
}
