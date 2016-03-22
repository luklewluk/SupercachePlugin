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

        $body = $this->getResponse()->getBody();
        $type = $body[0] === '{' ? CacheElement::TYPE_JAVASCRIPT : CacheElement::TYPE_HTML;

        if ($this->isEnabled()) {
            $this->checkExcludedPatterns($this->_request->getRequestUri());
            $this->checkCookies();
        }

        if (!$this->ignored && $this->getResponse()->getHttpResponseCode() == 200) {
            $cacheManager = new CacheManager($this->finder);
            $cacheElement = new CacheElement($this->_request->getRequestUri(),
                $body, $type);
            $cacheElement->setRawPath($this->_request->getRequestUri());
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
            if (isset($_SERVER["HTTP_CACHE_CONTROL"]) && $_SERVER["HTTP_CACHE_CONTROL"] == "no-cache") {
                $this->ignored = true;
            }

            if (isset($_SERVER["HTTP_PRAGMA"]) && $_SERVER["HTTP_PRAGMA"] == "no-cache") {
                $this->ignored = true;
            }
        }

        if (session_id()) {
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
}
