<?php

namespace Supercache;

use noFlash\SupercacheBundle\Cache\CacheManager;
use noFlash\SupercacheBundle\Filesystem\Finder;
use Pimcore\API\Plugin as PluginLib;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Supercache\Logger\LoggerProxy;
use Supercache\Install\HtaccessConfig;

/**
 * Class Plugin
 * @package Supercache
 *
 * Pimcore plugin class is necessary to run
 * the whole Supercache, set it as a Zend Plugin and
 * configure the plugin from Pimcore Extension Manager.
 */
class Plugin extends PluginLib\AbstractPlugin implements PluginLib\PluginInterface
{
    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * Creates directory to store cache files.
     *
     * @return bool
     */
    public static function install()
    {
        $path = self::getInstallPath();

        if (!is_dir($path)) {
            mkdir($path);
        }

        if (!is_file($path . '/.htaccess')) {
            file_put_contents($path . '/.htaccess', HtaccessConfig::getWebcacheHtaccess());
        }

        if (self::isInstalled()) {
            return "Supercache Plugin successfully installed.";
        } else {
            return "Supercache Plugin could not be installed.";
        }
    }

    /**
     * Returns path of cache directory
     *
     * @return string
     */
    public static function getInstallPath()
    {
        return __DIR__ . "/../../webcache";
    }

    /**
     * Checks if plugin is installed correctly.
     *
     * @return bool
     */
    public static function isInstalled()
    {
        if (is_dir(self::getInstallPath())) {
            if (is_file(self::getInstallPath() . '/.htaccess')) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Removes cache directory with all files inside.
     *
     * @return bool
     */
    public static function uninstall()
    {
        $it = new RecursiveDirectoryIterator(self::getInstallPath(), RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir(self::getInstallPath());

        if (!self::isInstalled()) {
            return "Supercache Plugin successfully uninstalled.";
        } else {
            return "Supercache Plugin could not be uninstalled.";
        }
    }

    /**
     * The main plugin method. It is executing as first and it is setting
     * Supercache as a Zend Plugin.
     *
     * @throws \Zend_EventManager_Exception_InvalidArgumentException
     */
    public function init()
    {
        $finder = new Finder(self::getInstallPath(), new LoggerProxy());
        $this->cacheManager = new CacheManager($finder);

        $front = \Zend_Controller_Front::getInstance();
        $front->registerPlugin(new Cache($finder), 902);

        parent::init();

        \Pimcore::getEventManager()->attach("document.preUpdate", array($this, "deleteCache"));
        \Pimcore::getEventManager()->attach("document.preDelete", array($this, "deleteCache"));
        \Pimcore::getEventManager()->attach("object.preUpdate", array($this, "deleteCache"));
        \Pimcore::getEventManager()->attach("object.preDelete", array($this, "deleteCache"));
        \Pimcore::getEventManager()->attach("system.cache.clearOutputCache", array($this, "deleteCache"));
        \Pimcore::getEventManager()->attach("admin.activateMaintenanceMode", array($this, "deleteCache"));
    }

    /**
     * Deletes cache entry recursively.
     *
     * @param $event
     */
    public function deleteCache($event)
    {
        $this->cacheManager->clear();
    }
}
