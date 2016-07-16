<?php

namespace noFlash\SupercacheBundle\Cache;


use noFlash\SupercacheBundle\Exceptions\SecurityViolationException;

/**
 * Represent single cache entry.
 */
class CacheElement
{
    /** @deprecated Will be moved to CacheType */
    const TYPE_HTML = 'html';

    /** @deprecated Will be moved to CacheType */
    const TYPE_JAVASCRIPT = 'js';

    /** @deprecated Will be moved to CacheType */
    const TYPE_BINARY = 'bin';

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $content;

    /**
     * @param string $path Cache path, eg. /sandbox
     * @param string $content Content to cache, eg. HTML
     * @param string $type Any valid type defined by self::TYPE_* constants
     *
     * @throws SecurityViolationException Specified cache path was found to be dangerous (eg. /../../sandbox)
     */
    public function __construct($path, $content, $type = self::TYPE_BINARY)
    {
        $this->setPath($path);
        $this->content = $content; //Since setter doesn't modify content skip calling it (performance!)
        $this->setType($type);
    }

    /**
     * Sets cached element path
     *
     * @param string $path Cache path, eg. /sandbox
     */
    public function setPath($path)
    {
        $this->path = urldecode($path);
    }

    /**
     * Sets cached element path without url decoding
     *
     * @param string $path Cache path, eg. /sandbox
     */
    public function setRawPath($path)
    {
        $this->path = $path;
    }

    /**
     * Provides cached element path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Sets element type
     *
     * @param string $type Any valid type defined by self::TYPE_* constants
     *
     * @throws \InvalidArgumentException
     */
    public function setType($type)
    {
        if ($type !== self::TYPE_HTML && $type !== self::TYPE_JAVASCRIPT && $type !== self::TYPE_BINARY) {
            throw new \InvalidArgumentException('Invalid type specified');
        }

        $this->type = $type;
    }

    /**
     * Provides cached element type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Provides cache element contents
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Sets element content
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = (string)$content;
    }
}
