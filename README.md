# SupercachePlugin
Static pages &amp; files caching system for Pimcore.

# Installation
```
composer require luklewluk/supercache
```

Also you will need to make changes in your rewrite configuration:
 
####Apache:


Edit your Pimcore .htaccess file by adding the following lines:
 
```
### >>>SUPERCACHE PLUGIN
RewriteCond %{REQUEST_METHOD} !^(GET|HEAD) [OR]
RewriteCond %{QUERY_STRING} !^$
RewriteRule . - [S=3]

RewriteCond %{DOCUMENT_ROOT}/plugins/Supercache/webcache/%{HTTP_HOST}/$1/index.html -f
RewriteRule ^(.*) %{DOCUMENT_ROOT}/plugins/Supercache/webcache/%{HTTP_HOST}/$1/index.html [L]

RewriteCond %{DOCUMENT_ROOT}/plugins/Supercache/webcache/%{HTTP_HOST}/$1/index.js -f
RewriteRule ^(.*) %{DOCUMENT_ROOT}/plugins/Supercache/webcache/%{HTTP_HOST}/$1/index.js [L]

RewriteCond %{DOCUMENT_ROOT}/plugins/Supercache/webcache/%{HTTP_HOST}/$1/index.bin -f
RewriteRule ^(.*) %{DOCUMENT_ROOT}/plugins/Supercache/webcache/%{HTTP_HOST}/$1/index.bin [L]
### <<<SUPERCACHE PLUGIN
``` 
 
It should be located after 

`# forbid the direct access to pimcore-internal data (eg. config-files, ...)` 

and before 

`# basic zend-framework setup see: http://framework.zend.com/manual/en/zend.controller.html` 

####Nginx:

Virtual host configuration:

Replace:

```
try_files $uri $uri/ /index.php?$args;
```

To:

```
try_files /plugins/Supercache/webcache/$http_host/$request_uri/index.js /plugins/Supercache/webcache/$http_host/$request_uri/index.html $uri $uri/ /index.php?$args;
```

# Some tests
Simple Pimcore blog and request time (TTFB) per page:

1. Supercache - ~0.37ms

2. Output Cache - ~31.5ms

3. Pimcore without extra cache - ~79.5ms

# Cache cleaning
Currently any change clean the cache. It turned out to be the best solution especially if someone wants to use Supercache in really complex website with many object-document dependencies.
If you want to clean cache manually you can do it by one of method below:

1. Clean "Output Cache" (since Pimcore 4.0)
2. Save any document or object in the Administration Panel.
3. Delete everything inside ./plugins/Supercache/webcache except .htaccess

Also Supercache is cleaned on maintenance mode activation (since Pimcore 4.0).


# Issues
### JSON response
Note: It can be useful if you want to cache JSON response as well.

Due to Pimcore/Zend good practices you suppose to encode your output to JSON by the helper with following command:
```
$this->_helper->json($json);
```
Unfortunately your response won't be cached because shutdown event can't be called. The easiest solution is replace it to:
```
echo $this->_helper->json($json, false);
```

