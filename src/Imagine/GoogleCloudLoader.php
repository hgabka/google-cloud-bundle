<?php

namespace Hgabka\GoogleCloudBundle\Imagine;

use Google\Cloud\Storage\StorageClient;
use Liip\ImagineBundle\Binary\Loader\LoaderInterface;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Model\Binary;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Mime\MimeTypes;

#[Autoconfigure(tags: [
    ['name' => 'liip_imagine.binary.loader', 'loader' => 'google_cloud'],
])]
class GoogleCloudLoader implements LoaderInterface
{
    public function __construct(private readonly StorageClient $client, private readonly string $bucket)
    {
    }

    public function find($path)
    {
        if (str_starts_with($path, $this->getBaseUrl())) {
            $path = str_replace($this->getBaseUrl(), '', $path);
        }

        $bucket = $this->client->bucket($this->bucket);
        $object = $bucket->object($path);

        if (!$object->exists()) {
            throw new NotLoadableException(sprintf('Source image "%s" not found.', $path));
        }

        $mime = $object->info()['contentType'];
        $mimeTypes = new MimeTypes();

        return new Binary(
            $object->downloadAsString(),
            $mime,
            $mimeTypes->getExtensions($mime)[0] ?? 'png'
        );
    }

    public function getBaseUrl(): string
    {
        return 'https://storage.googleapis.com/' . $this->bucket . '/';
    }
}
