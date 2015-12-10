<?php

namespace Supercache;

use noFlash\SupercacheBundle\Cache\CacheManager;
use noFlash\SupercacheBundle\Filesystem\Finder;
use Pimcore\API\Plugin as PluginLib;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Supercache\Logger\LoggerProxy;

class Plugin extends PluginLib\AbstractPlugin implements PluginLib\PluginInterface {

    static protected $dirPath = 'plugins/Supercache/webcache/';
    protected $cacheManager;
    protected $documentManager;

    public function init() {

        $this->documentManager = new DocumentManager();

        $finder = new Finder(self::$dirPath, new LoggerProxy());
        $this->cacheManager = new CacheManager($finder);

        $front = \Zend_Controller_Front::getInstance();
        $front->registerPlugin(new Cache($finder, $this->documentManager), 902);

        parent::init();

        \Pimcore::getEventManager()->attach("document.preUpdate", array($this, "deleteCache"));
        \Pimcore::getEventManager()->attach("document.preDelete", array($this, "deleteCache"));

    }

    public function deleteCache ($event) {
        // TODO: Delete cache in pages which use snippets
        $path = $this->documentManager->getPathByEvent($event);
        $this->cacheManager->deleteEntryRecursive($path);
    }

	public static function install (){
        mkdir(self::$dirPath);
        return true;
	}
	
	public static function uninstall (){
        $it = new RecursiveDirectoryIterator(self::$dirPath, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir(self::$dirPath);
        return true;
	}

	public static function isInstalled () {
        if (file_exists(self::$dirPath)){
            return true;
        }
        else {
            return false;
        }
	}
}
