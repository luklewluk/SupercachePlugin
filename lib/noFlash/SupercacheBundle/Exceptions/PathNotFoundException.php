<?php

namespace noFlash\SupercacheBundle\Exceptions;


/**
 * Thrown when requested filesystem patch cannot be found.
 */
class PathNotFoundException extends FilesystemException
{
    /**
     * @param string $path Filename or path which cannot be found.
     * @param string $details
     */
    public function __construct($path, $details = '')
    {
        $message = 'Failed to locate filesystem path ' . $path;
        if (!empty($details)) {
            $message .= ' - ' . $details . '.';
        }

        parent::__construct($message);
    }
}
