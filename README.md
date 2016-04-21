# SupercachePlugin
Static pages &amp; files caching system for Pimcore.

# Installation
Because GitHub doesn't create zip & tarball including submodule files so the easiest way to get the plugin is clone repo by following command:

`git clone --recursive https://github.com/luklewluk/SupercachePlugin.git ./Supercache`

Plugin directory name should be "Supercache".

If you use Apache or LiteSpeed you need to modify your .htaccess file by your own by adding the following lines after `# forbid the direct access to pimcore-internal data (eg. config-files, ...)` and before `# basic zend-framework setup see: http://framework.zend.com/manual/en/zend.controller.html` sections:

####Apache:

```apacheconf
### >>>SUPERCACHE BUNDLE
RewriteCond %{REQUEST_METHOD} !^(GET|HEAD) [OR]
RewriteCond %{QUERY_STRING} !^$
RewriteRule . - [S=3]

RewriteCond %{DOCUMENT_ROOT}/plugins/Supercache/webcache/$1/index.html -f
RewriteRule ^(.*) %{DOCUMENT_ROOT}/plugins/Supercache/webcache/$1/index.html [L]

RewriteCond %{DOCUMENT_ROOT}/plugins/Supercache/webcache/$1/index.js -f
RewriteRule ^(.*) %{DOCUMENT_ROOT}/plugins/Supercache/webcache/$1/index.js [L]

RewriteCond %{DOCUMENT_ROOT}/plugins/Supercache/webcache/$1/index.bin -f
RewriteRule ^(.*) %{DOCUMENT_ROOT}/plugins/Supercache/webcache/$1/index.bin [L]
### <<<SUPERCACHE BUNDLE
```

####Nginx:

Replace:

```
try_files $uri $uri/ /index.php?$args;
```

To:

```
try_files /plugins/Supercache/webcache/$request_uri/index.js /plugins/Supercache/webcache/$request_uri/index.html $uri $uri/ /index.php?$args;
```

To get a full boost I recommend to turn on Pimcore "Output Cache". 
Supercache supports "Output Cache" settings like excluded paths and excluded cookies. 
Of course it's optional and if you don't know when you will need both caching systems there's no reason for turning it on.

# Some tests
Simple Pimcore blog and request time (TTFB) per page:

1. Supercache - ~0.37ms

2. Output Cache - ~31.5ms

3. Pimcore without extra cache - ~79.5ms

# Issues
### Autoloader
Sometimes Zend autoloader cannot load depended classes. Then you have to just change name of directories:

```
psr -> Psr
```

```
log -> Log
```

```
noflash -> noFlash
```

```
supercachebundle -> SupercacheBundle
```

### JSON response
Due to Pimcore/Zend good practices you suppose to encode your output to JSON by the helper with following command:
```
$this->_helper->json($json);
```
Unfortunately your response won't be cached because shutdown event can't be called. The easiest solution is replace it to:
```
echo $this->_helper->json($json, false);
```

### Cache cleaning
Currently any change clean the cache. It turned out to be the best solution especially if someone wants to use Supercache in really complex website with many object-document dependencies.
If you want to clean cache manually you can do it by one of method below:

1. Clean "Output Cache" (since Pimcore 4.0)
2. Save any document or object in the Administration Panel.
3. Delete everything inside /plugins/Supercache/webcache except .htaccess

Also Supercache is cleaned on maintenance mode activation (since Pimcore 4.0).
