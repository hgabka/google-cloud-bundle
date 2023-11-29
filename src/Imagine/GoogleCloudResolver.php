<?php

namespace Hgabka\GoogleCloudBundle\Imagine;


use Google\Cloud\Storage\StorageClient;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Cache\Helper\PathHelper;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(tags: [
    ['name' => 'liip_imagine.cache.resolver', 'resolver' => 'google_cloud'],
])]
class GoogleCloudResolver implements ResolverInterface
{

    public function __construct(
        private readonly StorageClient $client, 
        private readonly FilterConfiguration $filterConfiguration,
        private readonly string $bucket,
        private readonly string $cachePrefix = 'uploads/cache',
    )
    {
    }

    private function changeFileExtension(string $path, string $filter): string
    {
        $format = $this->filterConfiguration->get($filter)['format'] ?? null;
        if (!$format) {
            return $path;
        }

        $info = pathinfo($path);
        $path = $info['dirname'] . \DIRECTORY_SEPARATOR . $info['filename'] . '.' . $format;

        return $path;
    }

    public function resolve($path, $filter)
    {
        $path = $this->changeFileExtension($path, $filter);

        return sprintf('%s/%s',
            rtrim($this->getBaseUrl(), '/'),
            ltrim($this->getFileUrl($path, $filter), '/')
        );
    }

    private function getFullPath($path, $filter): string
    {
        // crude way of sanitizing URL scheme ("protocol") part
        $path = str_replace('://', '---', $path);

        return $this->cachePrefix . '/' . $filter . '/' . ltrim($path, '/');
    }

    protected function getFileUrl($path, $filter): string
    {
        return PathHelper::filePathToUrlPath($this->getFullPath($path, $filter));
    }

    public function isStored($path, $filter): bool
    {
        $path = $this->changeFileExtension($path, $filter);
        $bucket = $this->client->bucket($this->bucket);

        $object = $bucket->object($this->getFileUrl($path, $filter));


        return $object->exists();
    }

    public function store(BinaryInterface $binary, $path, $filter): void
    {
        $path = $this->changeFileExtension($path, $filter);
        $bucket = $this->client->bucket($this->bucket);

        $bucket->upload($binary->getContent(), [
                'name' => $this->getFileUrl($path, $filter),
            ]
        );
    }


    public function getBaseUrl(): string
    {
        return 'https://storage.googleapis.com/' . $this->bucket;
    }

    public function remove(array $paths, array $filters): void
    {
        if (empty($paths) && empty($filters)) {
            return;
        }
        $bucket = $this->client->bucket($this->bucket);


        if (empty($paths)) {
            foreach ($filters as $filter) {
                $objects = $bucket->objects([
                    'prefix' => $this->cachePrefix . '/' . $filter
                ]);

                foreach ($objects as $object) {
                    $object->delete();
                }
            }

            return;
        }

        foreach ($paths as $path) {
            foreach ($filters as $filter) {
                $object = $bucket->object($this->getFileUrl($path, $filter));
                $object->delete();
            }
        }
    }

}
