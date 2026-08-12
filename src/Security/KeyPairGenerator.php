<?php

declare(strict_types=1);

namespace Finatto\LicenseClient\Security;

use Finatto\LicenseClient\Exceptions\ActivationException;

final class KeyPairGenerator
{
    public function __construct(private readonly ?string $openSslConfig = null) {}

    public function generate(): KeyPair
    {
        $options = ['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1'];
        $config = $this->openSslConfig ?? (is_file('/etc/ssl/openssl.cnf') ? '/etc/ssl/openssl.cnf' : null);
        if ($config !== null) $options['config'] = $config;
        $key = openssl_pkey_new($options);
        if ($key === false) throw new ActivationException('Could not generate the client private key.');
        $csrOptions = ['digest_alg' => 'sha256'];
        if ($config !== null) $csrOptions['config'] = $config;
        $csr = openssl_csr_new(['commonName' => 'finatto-license-client'], $key, $csrOptions);
        if ($csr === false) throw new ActivationException('Could not generate the certificate request.');
        if (! openssl_pkey_export($key, $privatePem, null, $config !== null ? ['config' => $config] : null) || ! openssl_csr_export($csr, $csrPem)) {
            throw new ActivationException('Could not export the client credentials.');
        }
        return new KeyPair($privatePem, $csrPem);
    }
}
