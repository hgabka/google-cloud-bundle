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

    public $bucketName = '';

    public function setFolderDepth(int $depth)
    {
        $this->folderDepth = $depth;
    }

    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;

        return $this;
    }

    public function getBucketName(): string
    {
        return $this->bucketName;
    }

    public function setBucketName(string $bucketName): self
    {
        $this->bucketName = $bucketName;

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
    public function canHandle($object)
    {
        if ($object instanceof File ||
            ($object instanceof Media &&
                ((!empty($object->getContent()) && is_file($object->getContent())) || 'google_cloud' === $object->getLocation()))
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

    public function prepareMedia(Media $media)
    {
        parent::prepareMedia($media);

        $media->setUrl('https://storage.googleapis.com/' . $this->bucketName . ($media->isProtected() ? $this->protectedMediaPath : $this->mediaPath) . $this->getFilePath($media));
        $media->setLocation('google_cloud');

        if ($media->getContent() && str_starts_with($media->getContentType(), 'image')) {
            $imageInfo = getimagesize($media->getContent());
            $width = $imageInfo[0];
            $height = $imageInfo[1];

            $media
                ->setMetadataValue('original_width', $width)
                ->setMetadataValue('original_height', $height);
        }
    }

    public function getImageUrl(Media $media, $basepath)
    {
        if (!str_starts_with($media->getContentType(), 'image')) {
            return null;
        }

        if (!$media->isProtected()) {
            return $media->getUrl();
        }

        return $this->urlGenerator->generate('HgabkaMediaBundle_admin_download_inline', ['media' => $media->getId()]);
    }
}
