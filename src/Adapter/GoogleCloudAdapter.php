<?php

namespace Hgabka\GoogleCloudBundle\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Adapter\MimeTypeProvider;
use Gaufrette\Adapter\SizeCalculator;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;

class GoogleCloudAdapter implements Adapter, SizeCalculator, MimeTypeProvider
{
    protected ?Bucket $bucket = null;

    protected StorageClient $client;

    public function __construct(
        protected ?string $directory,
        protected readonly ?string $bucketName = '',
    ) {
        if (!empty($this->directory)) {
            $this->directory = rtrim(ltrim($directory, '/'), '/') . '/';
        }
    }

    public function setClient(StorageClient $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function read($key)
    {
        if ($this->isDirectory($key)) {
            return false;
        }

        $object = $this->getObject($key);

        if (!$object->exists()) {
            return false;
        }

        return $object->downloadAsString();
    }

    public function write($key, $content)
    {
        $this->getBucket()->upload($content, [
            'name' => $this->directory . $key,
        ]);

        return $this->size($key);
    }

    public function rename($sourceKey, $targetKey)
    {
        $object = $this->getObject($sourceKey);

        if (!$object->exists()) {
            return false;
        }

        $content = $object->downloadAsString();

        $this->getBucket()->upload($content, [
            'name' => $this->directory . $targetKey,
        ]);

        $object->delete();
    }

    public function exists($key): bool
    {
        $object = $this->getObject($key);

        return $object->exists();
    }

    public function keys()
    {
        return $this->getBucket()->objects([
            'prefix' => $this->directory,
        ]);
    }

    public function mtime($key)
    {
        $object = $this->getObject($key);

        if (!$object->exists()) {
            return false;
        }

        $info = $object->info();

        return $info['updated'] ?? false;
    }

    /**
     * {@inheritdoc}
     *
     * Can also delete a directory recursively when the given $key matches a
     * directory.
     */
    public function delete($key)
    {
        $bucket = $this->getBucket();
        if ($this->isDirectory($key)) {
            $objects = $bucket->objects([
                'prefix' => $this->directory . $key,
            ]);

            foreach ($objects as $object) {
                $object->delete();
            }

            return true;
        } elseif ($this->exists($key)) {
            $this->getObject($key)->delete();

            return true;
        }

        return false;
    }

    /**
     * @param string $key
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     *
     * @return bool
     */
    public function isDirectory($key): bool
    {
        return str_ends_with($key, '/');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function size($key)
    {
        $object = $this->getObject($key);

        if (!$object->exists()) {
            return false;
        }

        $info = $object->info();

        return $info['size'] ?? 0;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function mimeType($key)
    {
        $object = $this->getObject($key);

        if (!$object->exists()) {
            return false;
        }

        $info = $object->info();

        return $info['contentType'] ?? false;
    }

    protected function getBucket(): ?Bucket
    {
        if (null === $this->bucket) {
            $this->bucket = $this->client->bucket($this->bucketName);
        }

        return $this->bucket;
    }

    protected function getObject($key)
    {
        return $this->getBucket()->object($this->directory . $key);
    }
}
