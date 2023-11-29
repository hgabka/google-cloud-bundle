<?php

namespace Hgabka\GoogleCloudBundle\Media;

use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Form\File\FileType;
use Hgabka\MediaBundle\Helper\File\FileHandler;
use Hgabka\MediaBundle\Helper\File\FileHelper;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AutoconfigureTag('hgabka_media.media_handler')]
class GoogleCloudHandler extends FileHandler
{
    public const TYPE = 'google_cloud';

    public $mediaPath;

    /**
     * @var string
     */
    public $protectedMediaPath;

    /**
     * Files with a blacklisted extension will be converted to txt.
     *
     * @var array
     */
    private $blacklistedExtensions = [];

    /** @var int */
    private $folderDepth;

    public function setFolderDepth(int $depth)
    {
        $this->folderDepth = $depth;
    }

    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;

        return $this;
    }

    /**
     * Inject the blacklisted.
     */
    public function setBlacklistedExtensions(array $blacklistedExtensions)
    {
        $this->blacklistedExtensions = $blacklistedExtensions;
    }

    /**
     * Inject the path used in media urls.
     *
     * @param string $mediaPath
     */
    public function setMediaPath($mediaPath)
    {
        $this->mediaPath = $mediaPath;
    }

    /**
     * Inject the path used in media urls.
     *
     * @param string $mediaPath
     */
    public function setProtectedMediaPath($mediaPath)
    {
        $this->protectedMediaPath = $mediaPath;
    }

    public function getName()
    {
        return 'Google Cloud Handler';
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return FileType::class;
    }

    /**
     * @param mixed $object
     *
     * @return bool
     */
    public function canHandle($object): bool
    {
        if ($object instanceof File ||
            ($object instanceof Media &&
                'google_cloud' === $object->getLocation())
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return FileHelper
     */
    public function getFormHelper(Media $media)
    {
        return new FileHelper($media, $this->mediaPath);
    }
}
