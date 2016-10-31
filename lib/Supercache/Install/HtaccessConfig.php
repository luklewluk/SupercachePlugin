<?php

namespace Supercache\Install;

/**
 * Store htaccess config as static accessible constants
 *
 * @package SupercachePlugin
 * @author  Lukasz Lewandowski <luklewluk@gmail.com>
 */
class HtaccessConfig
{
    /**
     * Get htaccess file content for "webcache" folder
     *
     * @return string
     */
    public static function getWebcacheHtaccess()
    {
        $webcache = <<<HTACCESS
RemoveHandler .php
RemoveType .php
Options -ExecCGI

<IfModule mod_php5.c>
    php_flag engine off
</IfModule>

<IfModule mod_headers.c>
    Header set Cache-Control 'max-age=3600, must-revalidate'
</IfModule>

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/html A3600
</IfModule>

<IfModule mod_mime.c>
    AddType application/javascript .js
    AddType text/html .html
    AddType application/octet-stream .bin
</IfModule>
HTACCESS;

        return $webcache;
    }

    /**
     * Get Supercache htaccess config for Pimcore
     *
     * @return string
     */
    public static function getSiteHtaccess()
    {
        $site = <<<HTACCESS
### >>>SUPERCACHE BUNDLE
RewriteCond %{REQUEST_METHOD} !^(GET|HEAD) [OR]
RewriteCond %{QUERY_STRING} !^$
RewriteRule . - [S=3]

RewriteCond %{DOCUMENT_ROOT}/plugins/Supercache/webcache/%{HTTP_HOST}/$1/index.html -f
RewriteRule ^(.*) %{DOCUMENT_ROOT}/plugins/Supercache/webcache//%{HTTP_HOST}/$1/index.html [L]

RewriteCond %{DOCUMENT_ROOT}/plugins/Supercache/webcache/%{HTTP_HOST}/$1/index.js -f
RewriteRule ^(.*) %{DOCUMENT_ROOT}/plugins/Supercache/webcache/%{HTTP_HOST}/$1/index.js [L]

RewriteCond %{DOCUMENT_ROOT}/plugins/Supercache/webcache/%{HTTP_HOST}/$1/index.bin -f
RewriteRule ^(.*) %{DOCUMENT_ROOT}/plugins/Supercache/webcache/%{HTTP_HOST}/$1/index.bin [L]
### <<<SUPERCACHE BUNDLE'
HTACCESS;

        return $site;
    }
}
