<?php

namespace App\Services\Auth;

/**
 * Who the network directory says a person is. Only built after a successful
 * bind with that person's password, so holding one means the password was
 * checked, not merely that an account was found.
 */
final readonly class LdapIdentity
{
    /**
     * @param  string  $username  Directory username (sAMAccountName), e.g. "budi.santoso".
     * @param  string  $distinguishedName  For the logs only, never shown to users.
     * @param  string|null  $email  Company email (mail, else userPrincipalName), lower case.
     */
    public function __construct(
        public string $username,
        public string $distinguishedName,
        public ?string $email,
        public ?string $name,
    ) {}
}
