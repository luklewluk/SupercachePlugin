<?php

namespace Supercache;

use Pimcore\Model\Document\Page;
use Zend_EventManager_Event;

class DocumentManager
{
    protected function findPrettyUrl(Page $page)
    {
        return $page->getPrettyUrl();
    }

    /**
     * @param Zend_EventManager_Event $event
     * @return string
     */
    public function getPathByEvent(Zend_EventManager_Event $event)
    {
        /** @var Page $page */
        $page = $event->getTarget();
        $prettyUrl = $this->findPrettyUrl($page);
        return $prettyUrl !== null ? $prettyUrl : $page->getFullPath();
    }

    /**
     * @return string
     */
    public function getViewRender()
    {
        $layout = new \Zend_View_Helper_Layout();
        return $layout->getLayout()->render();
    }
}
