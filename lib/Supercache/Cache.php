<?php

namespace Supercache;

use noFlash\SupercacheBundle\Cache\CacheElement;
use noFlash\SupercacheBundle\Cache\CacheManager;
use noFlash\SupercacheBundle\Filesystem\Finder;
use Pimcore\Tool;
use Zend_Controller_Front;
use Zend_Controller_Plugin_Abstract;
use Zend_Controller_Request_Abstract;

/**
 * Class Cache
 * @package Supercache
 *
 * Handles all requests and creates static cache file for them.
 */
class Cache extends Zend_Controller_Plugin_Abstract
{
    /**
     * @var Finder
     */
    protected $finder;

    /**
     * @var string
     */
    protected $config;

    /**
     * @var bool
     */
    protected $ignored = false;

    /**
     * @var bool
     */
    protected $minifyHtml = true;


    /**
     * Cache constructor.
     * @param Finder $finder
     */
    public function __construct(Finder $finder)
    {
        $this->config = \Pimcore\Config::getSystemConfig();
        $this->finder = $finder;
    }

    /**
     * Method is executed as one of the last one in the whole Pimcore.
     * Saves a full static page if request is eligible to cache.
     */
    public function dispatchLoopShutdown()
    {
        $this->checkRequest($this->_request);

        // test if HTML minification is required
        // otherwise use standard HTML
        $body = ($this->minifyHtml)?$this->_minifyHtml($this->getResponse()->getBody()):$this->getResponse()->getBody();
        $type = $body[0] === '{' ? CacheElement::TYPE_JAVASCRIPT : CacheElement::TYPE_HTML;

        if ($this->isEnabled()) {
            $this->checkExcludedPatterns($this->_request->getRequestUri());
            $this->checkCookies();
        }

        if (!$this->ignored && $this->getResponse()->getHttpResponseCode() == 200) {
            $cacheManager = new CacheManager($this->finder);
            $cacheElement = new CacheElement('/'.$_SERVER['HTTP_HOST'].$this->_request->getRequestUri(),
                $body, $type);
            $cacheElement->setRawPath('/'.$_SERVER['HTTP_HOST'].$this->_request->getRequestUri());
            $cacheManager->saveElement($cacheElement);
        }
    }

    /**
     * Only GET requests can be processed.
     * Also check headers for HTTPS and ignore caching for sessions.
     *
     * @param \Zend_Controller_Request_Abstract $request
     */
    protected function checkRequest(\Zend_Controller_Request_Abstract $request)
    {
        if (!$request->isGet()) {
            $this->ignored = true;
        }

        if (!$request->isSecure()) {
            if (isset($_SERVER["HTTP_CACHE_CONTROL"]) && $_SERVER["HTTP_CACHE_CONTROL"] === "no-cache") {
                $this->ignored = true;
            }

            if (isset($_SERVER["HTTP_PRAGMA"]) && $_SERVER["HTTP_PRAGMA"] === "no-cache") {
                $this->ignored = true;
            }
        }

        if (session_id() || isset($_COOKIE['pimcore_admin_sid'])) {
            $this->ignored = true;
        }
    }

    /**
     * Checks if Output Cache is enabled in Pimcore.
     *
     * @return bool
     */
    protected function isEnabled()
    {
        if ($this->config->cache) {
            $this->config = $this->config->cache;

            if (!$this->config->enabled) {
                return false;
            }

            if (\Pimcore::inDebugMode()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Using Output Cache settings to exclude unwanted patterns.
     *
     * @param string $uri
     */
    protected function checkExcludedPatterns($uri)
    {
        $excludePatterns = [];
        if ($this->config->excludePatterns) {
            $confExcludePatterns = explode(",", $this->config->excludePatterns);
            if (!empty($confExcludePatterns)) {
                $excludePatterns = $confExcludePatterns;
            }
        }

        foreach ($excludePatterns as $pattern) {
            if (@preg_match($pattern, $uri)) {
                $this->ignored = true;
            }
        }
    }

    /**
     * Using Output Cache settings to ignore caching for requests with
     * unwanted cookies.
     */
    protected function checkCookies()
    {
        if ($this->config->excludeCookie) {
            $cookies = explode(",", strval($this->config->excludeCookie));

            foreach ($cookies as $cookie) {
                if (!empty($cookie) && isset($_COOKIE[trim($cookie)])) {
                    $this->ignored = true;
                }
            }
        }

        if (isset($_COOKIE["pimcore_admin_sid"])) {
            $this->ignored = true;
        }
    }

    /**
     * Minifies the HTML
     *
     * @param string $sHtml Source to minify
     * @return mixed|string Minified output
     */
    private function _minifyHtml($sHtml = "") {
        if (strlen($sHtml) > 0) {
            $aReplace = array(
                // remove JS line comments (simple only); do NOT remove lines containing URL (e.g. 'src="http://server.com/"')!!!
                '~//[a-zA-Z0-9 ]+$~m' => '',
                //remove new-line after JS's function or condition start; join with next line
                '/\)[\r\n\t ]?{[\r\n\t ]+/s' => '){',
                '/,[\r\n\t ]?{[\r\n\t ]+/s' => ',{',
                //remove "empty" lines containing only JS's block end character; join with next line (e.g. "}\n}\n</script>" --> "}}</script>"
                '/}[\r\n\t ]+/s' => '}',
                '/}[\r\n\t ]+,[\r\n\t ]+/s' => '},',
                //remove tabs before and after HTML tags
                '/\>[^\S ]+/s' => '>',
                '/[^\S ]+\</s' => '<',
                //shorten multiple whitespace sequences; keep new-line characters because they matter in JS!!!
                '/([\t ])+/s' => ' ',
                //remove leading and trailing spaces
                '/^([\t ])+/m' => '',
                '/([\t ])+$/m' => '',
                //remove empty lines (sequence of line-end and white-space characters)
                '/[\r\n]+([\t ]?[\r\n]+)+/s' => "\n",
                //remove empty lines (between HTML tags); cannot remove just any line-end characters because in inline JS they can matter!
                '/\>[\r\n\t ]+\</s' => '><',
                //remove new-line after JS's line end (only most obvious and safe cases)
                '/\),[\r\n\t ]+/s' => '),',
                //remove quotes from HTML attributes that does not contain spaces; keep quotes around URLs!
                '~([\r\n\t ])?([a-zA-Z0-9]+)="([a-zA-Z0-9_/\\-]+)"([\r\n\t ])?~s' => '$1$2=$3$4', //$1 and $4 insert first white-space character found before/after attribute
            );

            $sHtml = preg_replace(array_keys($aReplace), array_values($aReplace), $sHtml);
        }

        return $sHtml;
    }
}
