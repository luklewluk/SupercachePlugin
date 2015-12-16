<?php

namespace Supercache;

use Pimcore\Model\Document\Page;
use Zend_EventManager_Event;

/**
 * Class DocumentManager
 * @package Supercache
 *
 * Class which is created to managing Pimcore documents.
 */
class DocumentManager
{
    /**
     * Find Pimcore document URL
     * @param Zend_EventManager_Event $event
     * @return string
     */
    public function getPathByEvent(Zend_EventManager_Event $event)
    {
        $page = $event->getTarget();
        $prettyUrl = $this->findPrettyUrl($page);
        return $prettyUrl !== null ? $prettyUrl : $page->getFullPath();
    }

    /**
     * Try to get "pretty url" if is set
     * @param $page
     * @return null|string
     */
    protected function findPrettyUrl($page)
    {
        if ($page instanceof Page) {
            return $page->getPrettyUrl();
        }
        return null;
    }
}
