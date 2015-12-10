<?php

namespace Supercache;

use noFlash\SupercacheBundle\Cache\CacheElement;
use noFlash\SupercacheBundle\Cache\CacheManager;
use noFlash\SupercacheBundle\Filesystem\Finder;
use Zend_Controller_Plugin_Abstract;
use Zend_Controller_Request_Abstract;

class Cache extends Zend_Controller_Plugin_Abstract
{
    private $finder;
    private $documentManager;

    public function __construct(Finder $finder, DocumentManager $documentManager)
    {
        $this->finder = $finder;
        $this->documentManager = $documentManager;
    }

    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        //TODO: Routing without htaccess
    }

    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        if ($request->getParam('module') !== 'admin' && empty($_REQUEST)) {
            $cacheManager = new CacheManager($this->finder);
            $cacheElement = new CacheElement($_SERVER['REQUEST_URI'], $this->documentManager->getViewRender(), CacheElement::TYPE_HTML);
            $cacheManager->saveElement($cacheElement);
        }
    }
}
