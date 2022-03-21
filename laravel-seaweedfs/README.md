Laravel-SeaweedFS
================

A Laravel integration of [seaweedfs-client](https://github.com/tystuyfzand/seaweedfs-client) with implementations making it useful for file storage, and using Laravel's own cache/database functionality for caching and mapping.

This package is pretty much untested and might not be production ready.

Configuration
-------------

All configuration options can be found in `config/seaweedfs.php` after publishing the vendor config:

```
php artisan vendor:publish --provider="SeaweedFS\Laravel\SeaweedFSProvider"
```

Required keys for a connection are `master`, optional are `cache`, `cache_store`, and `scheme`.

Storage Mapping
---------------

As mentioned in [flysystem-seaweedfs](https://github.com/tystuyfzand/flysystem-seaweedfs), this flysystem implementation uses a Mapping class that does **NOT** use the SeaweedFS Filer.

That being said, any read/write operations can use paths that map to a seaweedfs file, and URLs to the files may be generated using the `Storage::url()` method.