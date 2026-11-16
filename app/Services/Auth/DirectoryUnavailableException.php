<?php

namespace App\Services\Auth;

use RuntimeException;

/**
 * The network directory (LDAP) could not answer: not configured, unreachable,
 * the service account was refused or the search failed. This is an outage,
 * never a wrong password, and is reported to the error log.
 */
class DirectoryUnavailableException extends RuntimeException
{
    //
}
