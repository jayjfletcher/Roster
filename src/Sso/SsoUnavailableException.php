<?php

declare(strict_types=1);

namespace JayI\Roster\Sso;

use RuntimeException;

final class SsoUnavailableException extends RuntimeException
{
    public static function forProtocol(string $protocol): self
    {
        $packages = match ($protocol) {
            'saml' => 'laravel/socialite socialiteproviders/saml2',
            'azure' => 'laravel/socialite socialiteproviders/microsoft-azure',
            default => 'laravel/socialite firebase/php-jwt',
        };

        return new self("Single sign-on with [{$protocol}] needs: composer require {$packages}");
    }
}
