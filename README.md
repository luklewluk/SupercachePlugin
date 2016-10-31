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

RewriteCond %{DOCUMENT_ROOT}/../webcache/%{HTTP_HOST}/$1/index.html -f
RewriteRule ^(.*) %{DOCUMENT_ROOT}/../webcache/$%{HTTP_HOST}/1/index.html [L]

RewriteCond %{DOCUMENT_ROOT}/../webcache/%{HTTP_HOST}/$1/index.js -f
RewriteRule ^(.*) %{DOCUMENT_ROOT}/../webcache/%{HTTP_HOST}/$1/index.js [L]

RewriteCond %{DOCUMENT_ROOT}/../webcache/%{HTTP_HOST}/$1/index.bin -f
RewriteRule ^(.*) %{DOCUMENT_ROOT}/../webcache/%{HTTP_HOST}/$1/index.bin [L]
### <<<SUPERCACHE PLUGIN
``` 
 
It should be located after 

`# forbid the direct access to pimcore-internal data (eg. config-files, ...)` 

and before 

`# basic zend-framework setup see: http://framework.zend.com/manual/en/zend.controller.html` 

