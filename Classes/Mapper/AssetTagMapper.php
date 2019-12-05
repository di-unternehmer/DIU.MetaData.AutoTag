<?php

namespace DIU\MetaData\AutoTag\Mapper;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\Exception\UnresolvedDependenciesException;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Persistence\Exception;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Repository\TagRepository;
use Neos\MetaData\Domain\Collection\MetaDataCollection;
use Neos\MetaData\Domain\Dto\Iptc;
use Neos\MetaData\Extractor\Domain\ExtractionManager;

/**
 * @Flow\Scope("singleton")
 */
class AssetTagMapper
{
    /**
     * @Flow\InjectConfiguration(path="setTagsFromIptcKeywords")
     * @var bool
     */
    protected $setTagsFromIptcKeywords;

    /**
     * @Flow\InjectConfiguration(path="setCopyrightNoticeFromIptc")
     * @var bool
     */
    protected $setCopyrightNoticeFromIptc;

    /**
     * @Flow\InjectConfiguration(path="setTitleFromIptcTitle")
     * @var bool
     */
    protected $setTitleFromIptcTitle;

    /**
     * @Flow\InjectConfiguration(path="setCaptionFromIptcDescription")
     * @var bool
     */
    protected $setCaptionFromIptcDescription;

    /**
     * @Flow\Inject
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @FLow\Inject
     * @var ExtractionManager
     */
    protected $extractionManager;

    /**
     * @Flow\Inject
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @param Asset $asset
     *
     * @return void
     * @throws IllegalObjectTypeException
     * @throws UnresolvedDependenciesException
     * @throws \Neos\Flow\ObjectManagement\Exception\UnknownObjectException
     * @throws \Neos\MetaData\Extractor\Exception\ExtractorException
     */
    public function mapMetaData(Asset $asset): void
    {
        /** @var MetaDataCollection $metaData */
        $metaDataCollection = $this->extractionManager->extractMetaData($asset);
        if (!$metaDataCollection) {
            return;
        }

        /** @var Iptc $iptcAssetData */
        $iptcAssetData = $metaDataCollection->get('iptc');
        if (!$iptcAssetData) {
            return;
        }

        // set keywords as tags in media backend
        $iptcKeywords = $iptcAssetData->getKeywords();

        if ($this->setTagsFromIptcKeywords && $iptcKeywords && count($asset->getTags()) === 0) {
            foreach ($iptcKeywords as $iptcKeyword) {
                // search for keyword in tag repository
                $repositoryTag = $this->tagRepository->findOneByLabel($iptcKeyword);

                // create tag if missing
                if (!$repositoryTag) {
                    $repositoryTag = new Tag($iptcKeyword);
                    $this->tagRepository->add($repositoryTag);
                }

                // add tag to asset
                $asset->addTag($repositoryTag);
            }
        }

        // add description to caption if not set yet
        if ($this->setCaptionFromIptcDescription && $iptcAssetData->getDescription() && !trim($asset->getCaption())) {
            $asset->setCaption($iptcAssetData->getDescription());
        }

        // add copyright notice
        if ($this->setCopyrightNoticeFromIptc && $iptcAssetData->getCopyrightNotice() && !$asset->getCopyrightNotice()) {
            $asset->setCopyrightNotice($iptcAssetData->getCopyrightNotice());
        }

        // add title/headline from iptc if asset title contains only filename like DC12391.JPG or is not set
        if ( $this->setTitleFromIptcTitle && (!trim($asset->getTitle()) || $this->isFilename($asset->getTitle())) )
        {
            // if a title in iptc exists and it's not a filename other wise check if headline is set
            if ($iptcAssetData->getTitle() && !$this->isFilename($iptcAssetData->getTitle())){
                $asset->setTitle($iptcAssetData->getTitle());
            } elseif ($iptcAssetData->getHeadline()){
                $asset->setTitle($iptcAssetData->getHeadline());
            }
        }
    }

    protected function isFilename($string) : bool
    {
        $file_parts = pathinfo(trim($string));
        return isset($file_parts['extension']);
    }
}
