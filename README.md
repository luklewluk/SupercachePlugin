# SupercachePlugin
Static pages &amp; files caching system for Pimcore.

# Installation
Because GitHub doesn't create zip & tarball including submodule files so the easiest way to get the plugin is clone repo by following command:

`git clone --recursive https://github.com/luklewluk/SupercachePlugin.git`

Unfortunately for now only .htaccess setting is supported.

Also you need to modify your .htaccess file by your own by adding the following lines after `# forbid the direct access to pimcore-internal data (eg. config-files, ...)` and before `# basic zend-framework setup see: http://framework.zend.com/manual/en/zend.controller.html` sections:
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

To get a full boost I recommend to turn on Pimcore "Output Cache". 
Supercache supports "Output Cache" settings like excluded paths and excluded cookies. 
Of course it is optional and if you don't know cases when you will need both caching systems there is no reason for turning it on.

# Some tests
Simple Pimcore blog and request time (TTFB) per page:

1. Supercache - ~0.37ms

2. Output Cache - ~31.5ms

3. Pimcore without extra cache - ~79.5ms

