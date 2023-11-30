# Google cloud bundle

The bundle uses the Google Storage Client. The auth json can be obtained using the following steps:

1. Go to https://console.cloud.google.com/apis/credentials

2. On the top left there is a blue "create credentials" button click it and select "service account key."

3. Choose the service account you want, and select "JSON" as the key type. It should allow give you a json to download

After you get the json file, put it into a directory somewhere in the poject, and add the following service definition (e.g. in config/packages/google_cloud_storage.yaml):

```
services:
    google_cloud_storage_client:
      class: 'Google\Cloud\Storage\StorageClient'
      arguments:
        -   keyFilePath: '%kernel.project_dir%/config/packages/google_cloud_storage/key.json'
```

Replace keyFilePath with the path you saved the json into.

Now you can use filesystem service '@google_cloud.filesystem', e.g:

```
    App\Controller\TestController:
        arguments:
            $fileSystem: '@google_cloud.filesystem'
```

This is a Gaufrette filesystem, so you can then use it:
```
$this->fileSystem->write('test.txt', 'sasadas2');
```

This will then write to the  the bucket.

Bucket name must be configured in config/packages/hgabka_google_cloud.yaml:
```
hgabka_google_cloud:
    bucket: 'my-bucket'
```

## Liip imagine

There is a resolver that  saves the filtered image in Google Cloud. The cache is created in the liip imagine cache directory (hgabka_media.liip_imagine_cache_prefix, see Media bundle)
The 'google_cloud' resolver can be set in the filter call:


```
asset(imagePath)|imagine_filter('filter', [], 'google_cloud')
```



Or in the liip imagine config for all images:
```
liip_imagine:
    cache: google_cloud
```


If the original image is also in Google Cloud, the normal WebPatResolver will not work. For this purpose there is a loader that loads the image from the Google Cloud image url. This can be set on filter level:
```
    filter_sets:
        my_filter:
            quality: 100
            data_loader: google_cloud
```



Or globally:
```
liip_imagine:
    data_loader: google_cloud
```

You can mix the two, but the Google Cloud loader will only work for images in Google Cloud, while the default loader will only work for local images.

## Media bundle


The bundle has a media handler, whose priority is higher than the ones of media bundle. So after enabling this bundle, all uploads will go to Google Cloud. Old local images will be handled normally, only the new uploads will go to the cloud.
