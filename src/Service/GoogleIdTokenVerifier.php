<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Verifies Google Sign-In ID tokens using Google's JWKS (replaces deprecated tokeninfo).
 */
final class GoogleIdTokenVerifier
{
    private const JWKS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    private const VALID_ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private string $googleClientId,
    ) {
    }

    /**
     * @return array<string, mixed> Verified token claims (email, sub, given_name, …)
     */
    public function verify(string $idToken): array
    {
        $parts = explode('.', $idToken);

        if (\count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid ID token format.');
        }

        $header = json_decode($this->base64UrlDecode($parts[0]), true);
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);

        if (!\is_array($header) || !\is_array($payload)) {
            throw new \InvalidArgumentException('Invalid ID token payload.');
        }

        $exp = (int) ($payload['exp'] ?? 0);
        if ($exp > 0 && $exp < time()) {
            throw new \InvalidArgumentException('ID token has expired.');
        }

        $iss = (string) ($payload['iss'] ?? '');
        if (!\in_array($iss, self::VALID_ISSUERS, true)) {
            throw new \InvalidArgumentException('Invalid token issuer.');
        }

        if (!$this->isAudienceValid($payload)) {
            throw new \InvalidArgumentException('Invalid token audience.');
        }

        $kid = $header['kid'] ?? null;
        if (!\is_string($kid) || $kid === '') {
            throw new \InvalidArgumentException('Token header is missing key id.');
        }

        $publicKey = openssl_pkey_get_public($this->getPublicKeyPem($kid));
        if ($publicKey === false) {
            throw new \RuntimeException('Unable to load Google public key.');
        }

        $signed = $parts[0].'.'.$parts[1];
        $signature = $this->base64UrlDecode($parts[2]);
        $verified = openssl_verify($signed, $signature, $publicKey, \OPENSSL_ALGO_SHA256);

        if ($verified !== 1) {
            throw new \InvalidArgumentException('ID token signature is invalid.');
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function isAudienceValid(array $payload): bool
    {
        $expected = trim($this->googleClientId);

        if ($expected === '') {
            return true;
        }

        $aud = $payload['aud'] ?? null;
        if (\is_string($aud) && $aud === $expected) {
            return true;
        }

        $azp = $payload['azp'] ?? null;

        return \is_string($azp) && $azp === $expected;
    }

    private function getPublicKeyPem(string $kid): string
    {
        $jwks = $this->cache->get('google_jwks', function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            $response = $this->httpClient->request('GET', self::JWKS_URL);
            $data = $response->toArray();

            return \is_array($data['keys'] ?? null) ? $data['keys'] : [];
        });

        foreach ($jwks as $key) {
            if (!\is_array($key) || ($key['kid'] ?? null) !== $kid) {
                continue;
            }

            return $this->jwkToPem($key);
        }

        // Key rotation: refresh JWKS once and retry.
        $this->cache->delete('google_jwks');
        $jwks = $this->cache->get('google_jwks', function (ItemInterface $item) use ($kid): array {
            $item->expiresAfter(3600);

            $response = $this->httpClient->request('GET', self::JWKS_URL);
            $data = $response->toArray();

            return \is_array($data['keys'] ?? null) ? $data['keys'] : [];
        });

        foreach ($jwks as $key) {
            if (\is_array($key) && ($key['kid'] ?? null) === $kid) {
                return $this->jwkToPem($key);
            }
        }

        throw new \InvalidArgumentException('Google signing key not found.');
    }

    /**
     * @param array<string, mixed> $jwk
     */
    private function jwkToPem(array $jwk): string
    {
        if (($jwk['kty'] ?? '') !== 'RSA' || !isset($jwk['n'], $jwk['e'])) {
            throw new \InvalidArgumentException('Unsupported JWK.');
        }

        $modulus = $this->base64UrlDecode((string) $jwk['n']);
        $exponent = $this->base64UrlDecode((string) $jwk['e']);

        $modulus = $this->encodeLengthPrefixed("\x00".$modulus);
        $exponent = $this->encodeLengthPrefixed($exponent);

        $rsaPublicKey = $this->encodeLengthPrefixed(
            "\x30".$this->encodeLengthPrefixed("\x02".$modulus."\x02".$exponent),
        );

        $bitString = "\x00".$rsaPublicKey;
        $rsaOid = hex2bin('300D06092A864886F70D0101010500');
        $publicKeyInfo = "\x30".$this->encodeLengthPrefixed($rsaOid."\x03".$this->encodeLengthPrefixed($bitString));

        return "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode($publicKeyInfo), 64, "\n")
            ."-----END PUBLIC KEY-----\n";
    }

    private function encodeLengthPrefixed(string $data): string
    {
        $length = \strlen($data);

        if ($length < 128) {
            return \chr($length).$data;
        }

        $lenBytes = ltrim(pack('N', $length), "\x00");

        return \chr(0x80 | \strlen($lenBytes)).$lenBytes.$data;
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = \strlen($data) % 4;
        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64url encoding.');
        }

        return $decoded;
    }
}
