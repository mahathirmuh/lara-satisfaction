<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LDAP\Connection;

/**
 * Checks a network (Active Directory) password: the service account looks the
 * person up, then a second connection binds as that person with the password
 * they typed. Binding is the check; no password is read, compared or stored.
 *
 * Refusals that are easy to get wrong:
 *  - an empty password "binds" successfully in LDAP (unauthenticated bind),
 *    so it is refused before the directory is asked;
 *  - a disabled account can still bind on some servers, so userAccountControl
 *    is checked;
 *  - a username matching more than one entry is refused rather than resolved
 *    by taking the first;
 *  - a directory that cannot answer throws DirectoryUnavailableException
 *    instead of looking like a wrong password.
 *
 * Same design as the trilingual-docon application (same directory, same
 * LDAP_* settings).
 */
class LdapAuthenticator
{
    /**
     * Bit 2 of userAccountControl: ACCOUNTDISABLE.
     */
    protected const ACCOUNT_DISABLED = 0x0002;

    /**
     * Whether the directory address and the service account are configured.
     */
    public function isConfigured(): bool
    {
        return filled(config('ldap.url')) && filled(config('ldap.bind_dn'));
    }

    /**
     * The person, when the password is theirs; null otherwise.
     *
     * @throws DirectoryUnavailableException
     */
    public function authenticate(string $username, string $password): ?LdapIdentity
    {
        if (trim($username) === '' || $password === '') {
            return null;
        }

        if (! $this->isConfigured()) {
            throw new DirectoryUnavailableException('Network sign-in is not configured: set LDAP_URL and LDAP_BIND_DN.');
        }

        $connection = $this->connect();

        try {
            $entry = $this->find($connection, trim($username));

            if ($entry === null || $this->isDisabled($entry)) {
                return null;
            }

            $asUser = $this->connect(bindAsServiceAccount: false);

            try {
                if (! @ldap_bind($asUser, $entry['dn'], $password)) {
                    return null;
                }
            } finally {
                @ldap_unbind($asUser);
            }

            $email = $this->attribute($entry, (string) config('ldap.attributes.mail', 'mail'))
                ?? $this->attribute($entry, 'userprincipalname');

            return new LdapIdentity(
                username: $this->attribute($entry, (string) config('ldap.attributes.username', 'samaccountname')) ?? trim($username),
                distinguishedName: $entry['dn'],
                email: $email !== null ? Str::lower($email) : null,
                name: $this->attribute($entry, (string) config('ldap.attributes.name', 'displayname')),
                jobTitle: $this->attribute($entry, (string) config('ldap.attributes.job_title', 'title')),
                employeeId: $this->attribute($entry, (string) config('ldap.attributes.employee_id', 'employeeid')),
            );
        } finally {
            @ldap_unbind($connection);
        }
    }

    /**
     * A directory connection, bound as the service account unless told otherwise.
     *
     * @throws DirectoryUnavailableException
     */
    protected function connect(bool $bindAsServiceAccount = true): Connection
    {
        if (! extension_loaded('ldap')) {
            throw new DirectoryUnavailableException('The PHP ldap extension is not enabled on this server.');
        }

        // TLS options must be set globally before connecting: on a connection handle they are silently ignored.
        ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, config('ldap.tls_verify') ? LDAP_OPT_X_TLS_DEMAND : LDAP_OPT_X_TLS_NEVER);

        if (filled($caCertificate = config('ldap.ca_cert'))) {
            if (! is_readable((string) $caCertificate)) {
                throw new DirectoryUnavailableException("The LDAP_CA_CERT file [{$caCertificate}] cannot be read.");
            }

            ldap_set_option(null, LDAP_OPT_X_TLS_CACERTFILE, (string) $caCertificate);
        }

        $connection = @ldap_connect((string) config('ldap.url'));

        if ($connection === false) {
            throw new DirectoryUnavailableException('The LDAP_URL could not be used.');
        }

        $timeout = max(1, (int) config('ldap.timeout', 5));

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($connection, LDAP_OPT_NETWORK_TIMEOUT, $timeout);
        ldap_set_option($connection, LDAP_OPT_TIMELIMIT, $timeout);

        if ($bindAsServiceAccount && ! @ldap_bind($connection, (string) config('ldap.bind_dn'), (string) config('ldap.bind_password'))) {
            $error = ldap_error($connection);
            @ldap_unbind($connection);

            throw new DirectoryUnavailableException("The service account could not bind to the directory: {$error}.");
        }

        return $connection;
    }

    /**
     * The only directory entry for this username, or null.
     *
     * @return array<string, mixed>|null
     *
     * @throws DirectoryUnavailableException
     */
    protected function find(Connection $connection, string $username): ?array
    {
        $filter = str_replace(
            '{username}',
            ldap_escape($username, '', LDAP_ESCAPE_FILTER),
            (string) config('ldap.user_search_filter'),
        );

        $attributes = array_values(array_unique(array_map('strtolower', [
            ...array_values((array) config('ldap.attributes')),
            'userprincipalname',
            'useraccountcontrol',
        ])));

        $search = @ldap_search($connection, (string) config('ldap.user_search_base'), $filter, $attributes, 0, 2);

        if ($search === false) {
            throw new DirectoryUnavailableException('The directory search failed: '.ldap_error($connection).'.');
        }

        $entries = ldap_get_entries($connection, $search);
        $count = (int) ($entries['count'] ?? 0);

        if ($count > 1) {
            Log::warning('The LDAP user search matched more than one account; the sign-in was refused.', [
                'filter' => config('ldap.user_search_filter'),
            ]);
        }

        return $count === 1 ? $entries[0] : null;
    }

    /**
     * Whether the account is disabled in Active Directory.
     *
     * @param  array<string, mixed>  $entry
     */
    protected function isDisabled(array $entry): bool
    {
        $flags = $this->attribute($entry, 'useraccountcontrol');

        return $flags !== null && ((int) $flags & self::ACCOUNT_DISABLED) !== 0;
    }

    /**
     * First value of an attribute (names are lower case in the entry).
     *
     * @param  array<string, mixed>  $entry
     */
    protected function attribute(array $entry, string $name): ?string
    {
        $value = $entry[strtolower($name)][0] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
