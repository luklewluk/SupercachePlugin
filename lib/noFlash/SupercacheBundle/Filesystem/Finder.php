<?php

namespace noFlash\SupercacheBundle\Filesystem;


use FilesystemIterator;
use Iterator;
use noFlash\SupercacheBundle\Exceptions\FilesystemException;
use noFlash\SupercacheBundle\Exceptions\PathNotFoundException;
use noFlash\SupercacheBundle\Exceptions\SecurityViolationException;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

/**
 * Handles all filesystem operations.
 * This class always uses / (slash) as directory separator, however it accepts \.
 */
class Finder
{
    const CACHE_FILE_REGEX = '/index\.(html|js|bin)$/';

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string $cacheDir Patch to caching directory
     * @param LoggerInterface $logger
     *
     * @throws FilesystemException Unknown or invalid cache directory specified. It may be a permission problem.
     */
    public function __construct($cacheDir, LoggerInterface $logger)
    {
        $realCacheDir = $this->unixRealpath($cacheDir);

        if (!$realCacheDir) {
            throw new FilesystemException("Supercache data directory $cacheDir is invalid or inaccessible");
        }

        $this->cacheDir = $realCacheDir;
        $this->logger = $logger;
    }

    /**
     * Method works like realpath(), but it always return paths with slashes (while builtin one uses backslashes on Windos)
     *
     * @param $path
     *
     * @return string Unlike realpath() this method never returns false.
     */
    private function unixRealpath($path)
    {
        return (DIRECTORY_SEPARATOR==='/') ? (string)realpath($path) : str_replace('\\', '/', realpath($path));
    }

    /**
     * Provides real filesystem path to cache directory (with all variables and symlinks resolved)
     *
     * @return string
     */
    public function getRealCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * Converts relative path into physical path in filesystem.
     *
     * @param string $path
     *
     * @return string
     * @throws PathNotFoundException
     * @throws SecurityViolationException Thrown while path is not located under cache directory
     */
    private function getAbsolutePathFromRelative($path)
    {
        $absolute = $this->unixRealpath($this->cacheDir . '/' . $path);

        if (empty($absolute)) {
            throw new PathNotFoundException($path, 'entry cannot be located inside supercache directory');
        }

        if (strpos($absolute, $this->cacheDir) !== 0) {
            throw new SecurityViolationException("Requested path $path is located above supercache directory");
        }

        return $absolute;
    }

    /**
     * Provides raw list of cache files.
     *
     * @return Iterator|SplFileInfo[]
     *
     * @throw UnexpectedValueException Exception is thrown while cache directory cannot be opened or traversed.
     */
    public function getFilesList()
    {
        $dir = new RecursiveDirectoryIterator($this->cacheDir, FilesystemIterator::CURRENT_AS_FILEINFO);
        $ite = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);
        $files = new RegexIterator($ite, self::CACHE_FILE_REGEX);

        return $files;
    }

    /**
     * Get cache file content.
     *
     * @param string $path
     *
     * @return string|false Will return file contents or false if cannot be retrieved.
     * @throws PathNotFoundException
     * @throws SecurityViolationException Thrown while path is not located under cache directory
     * @throws \InvalidArgumentException Given path doesn't look like file created by this bundle
     */
    public function readFile($path)
    {
        try {
            $path = $this->getAbsolutePathFromRelative($path);

        } catch(PathNotFoundException $e) {
            return false;
        }

        if (!preg_match(static::CACHE_FILE_REGEX, $path)) {
            throw new \InvalidArgumentException("Finder can only read cache files - $path is not one of them");
        }

        return @file_get_contents($path);
    }

    /**
     * Deletes cache file.
     *
     * @param string $path
     *
     * @return bool
     * @throws PathNotFoundException
     * @throws SecurityViolationException Thrown while path is not located under cache directory
     * @throws \InvalidArgumentException Given path doesn't look like file created by this bundle
     */
    public function deleteFile($path)
    {
        $path = $this->getAbsolutePathFromRelative($path);
        if (!$path) {
            return false;
        }

        if (!preg_match(static::CACHE_FILE_REGEX, $path)) {
            throw new \InvalidArgumentException("Finder can only delete cache files - $path is not one of them");
        }

        return @unlink($path);
    }

    /**
     * Deletes EMPTY directory.
     *
     * @param string $path
     *
     * @return bool
     * @throws PathNotFoundException
     * @throws SecurityViolationException Thrown while path is not located under cache directory
     */
    public function deleteDirectory($path)
    {
        $path = $this->getAbsolutePathFromRelative($path);

        return ($path && @rmdir($path));
    }

    /**
     * Deletes directory with it's content.
     *
     *
     * @param string $path
     * @param bool $safeDelete By default this option is enabled and prevents deleting files and folders not created by
     *     that bundle.
     *
     * @return bool
     * @throws FilesystemException
     * @throws PathNotFoundException
     * @throws SecurityViolationException Thrown while path is not located under cache directory
     * @throws \RuntimeException Exception is used while unknown file is found and $safeDelete is enabled
     */
    public function deleteDirectoryRecursive($path, $safeDelete = true)
    {
        $path = $this->getAbsolutePathFromRelative($path);
        if (empty($path)) {
            return false;
        }

        $dir = new RecursiveDirectoryIterator($path,
            RecursiveDirectoryIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::UNIX_PATHS);
        $iterator = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);

        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            $realPath = $this->unixRealpath($fileInfo->getRealPath());

            if ($fileInfo->isDir()) {
                if (@!rmdir($realPath)) {
                    throw new FilesystemException('Failed to delete directory ' . $realPath);
                }

            } else {
                if (strpos($realPath, $this->cacheDir .  '/.') === 0) { //Skip dot-files in root directory
                    continue;
                }

                if ($safeDelete && !preg_match(self::CACHE_FILE_REGEX, $realPath)) {
                    throw new \RuntimeException('Finder found unknown file ' . $realPath . ' - this was not created by SupercacheBundle. Inspect this issue manually.');
                }

                if (@!unlink($realPath)) {
                    throw new FilesystemException('Failed to delete file ' . $realPath);
                }
            }
        }

        if ($path !== $this->cacheDir) {
            return rmdir($path);
        }

        return true;
    }

    /**
     * Saves content to given cache file.
     *
     * @param $path
     * @param $content
     *
     * @return bool
     * @throws FilesystemException
     * @throws SecurityViolationException Should never be thrown from this method. If thrown it means some security
     *     mechanisms failed to sanitize $path and file gets created outside cache directory. This method is smart
     *     enough to rollback that change by deleting file (so no harm is expected). If you seen that exception while
     *     calling this method fill bug report asap.
     * @throws \InvalidArgumentException Given path doesn't look like file created by this bundle.
     */
    public function writeFile($path, $content)
    {
        $fullPath = preg_replace('/(\/|\\\)+/','/', $this->cacheDir . '/' . $path); //Convert to absolute path with unix separators & remove duplicated slashed

        if (preg_match('/\/\.\.|\.\.\//', $fullPath)) { //Don't look at me that way, please...
            throw new SecurityViolationException("Requested path $path is invalid");
        }

        if (!preg_match(self::CACHE_FILE_REGEX, $fullPath)) {
            throw new \InvalidArgumentException("Finder can only create cache files - $path is not one of them");
        }

        $info = pathinfo($fullPath);
        if (!isset($info['dirname']) || (!is_dir($info['dirname']) && !mkdir($info['dirname'], 0777, true))) {
            throw new FilesystemException('Failed to create directory for ' . $path);
        }

        if (file_put_contents($fullPath, $content) === false) {
            $this->logger->error("Unable to write cache file $fullPath (requested write to $path)",
                array('content' => $content));

            return false;
        }

        //Last verification to be 100% sure we did the right thing...
        try {
            $this->getAbsolutePathFromRelative($path);

            return true;

        } catch (\Exception $e) {
            @unlink($fullPath); //Remove bogus file

            $this->logger->error("Cache file verification failed after write - file not found after writing to $fullPath (requested $path)");

            if($e instanceof SecurityViolationException) {
                $this->logger->error("Cache file verification failed after write - file not found after writing to $fullPath (requested $path)");
            }

            return false;
        }
    }

    /**
     * Checks if given cache file/folder is readable.
     *
     * @param $path
     *
     * @return bool
     * @throws SecurityViolationException Tried to access file outside cache directory.
     */
    public function isReadable($path)
    {
        try {
            $this->getAbsolutePathFromRelative($path);

            return true;

        } catch(PathNotFoundException $e) {
            return false;
        }
    }
}
