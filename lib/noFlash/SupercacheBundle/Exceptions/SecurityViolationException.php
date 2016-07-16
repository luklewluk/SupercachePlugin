<?php

namespace noFlash\SupercacheBundle\Exceptions;


use Exception;

/**
 * Thrown on serious security-related events - you should NEVER ignore that exception!
 */
class SecurityViolationException extends \RuntimeException
{
    /**
     * @inheritDoc
     */
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        $newMessage = "Security violation occurred, operation was aborted.";

        if (!empty($message)) {
            $newMessage .= ' ' . $message;
        }

        parent::__construct($newMessage, $code, $previous);
    }
}
