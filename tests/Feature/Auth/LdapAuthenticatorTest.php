<?php

namespace Tests\Feature\Auth;

use App\Services\Auth\DirectoryUnavailableException;
use App\Services\Auth\LdapAuthenticator;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * The refusals made before the directory is asked. The directory itself is
 * not reachable from the test suite; it is exercised against a real LDAP
 * server when the Docker image is verified.
 */
class LdapAuthenticatorTest extends TestCase
{
    /**
     * An empty password would be an unauthenticated bind, which LDAP answers
     * with success for any existing account.
     */
    #[TestWith(['budi.santoso', ''])]
    #[TestWith(['   ', 'network-password'])]
    public function test_blank_credentials_are_refused_without_asking_the_directory(string $username, string $password): void
    {
        config(['ldap.url' => 'ldap://directory.invalid:389', 'ldap.bind_dn' => 'CN=Reader,DC=example,DC=com']);

        $this->assertNull(app(LdapAuthenticator::class)->authenticate($username, $password));
    }

    public function test_a_relative_ca_certificate_path_is_read_from_the_project_root(): void
    {
        if (! extension_loaded('ldap')) {
            $this->markTestSkipped('The PHP ldap extension is not enabled.');
        }

        config([
            'ldap.url' => 'ldaps://directory.invalid:636',
            'ldap.bind_dn' => 'CN=Reader,DC=example,DC=com',
            'ldap.ca_cert' => 'certs/missing-ca.pem',
        ]);

        $this->expectException(DirectoryUnavailableException::class);
        $this->expectExceptionMessage('The LDAP_CA_CERT file ['.base_path('certs/missing-ca.pem').'] cannot be read.');

        app(LdapAuthenticator::class)->authenticate('budi.santoso', 'network-password');
    }

    public function test_unconfigured_directory_is_reported_as_unavailable_rather_than_as_a_wrong_password(): void
    {
        config(['ldap.url' => null, 'ldap.bind_dn' => null]);

        $this->expectException(DirectoryUnavailableException::class);

        app(LdapAuthenticator::class)->authenticate('budi.santoso', 'network-password');
    }
}
