<?php

namespace App\Services\Auth;

/**
 * A person account as listed by the network directory (read with the
 * service account, no password checked): what the employee import needs.
 */
final readonly class DirectoryPerson
{
    /**
     * @param  string  $username  Directory username (sAMAccountName).
     * @param  string|null  $email  Company e-mail (mail, else userPrincipalName), lower case.
     * @param  string|null  $jobTitle  Job title (title), which decides the roles.
     * @param  string|null  $employeeId  Employee ID (employeeID).
     * @param  string|null  $department  Directory department (department), e.g. "Supply Chain Management".
     * @param  string|null  $company  Directory company (company), e.g. "PT. Merdeka Tsingshan Indonesia".
     * @param  bool  $isEnabled  False when the account is disabled.
     */
    public function __construct(
        public string $username,
        public ?string $email,
        public ?string $name,
        public ?string $jobTitle,
        public ?string $employeeId,
        public ?string $department,
        public ?string $company,
        public bool $isEnabled,
    ) {}
}
