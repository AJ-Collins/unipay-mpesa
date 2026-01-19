<?php
// helpers/auth/JwtConfig.php

namespace helpers\auth\jwt;

use Yii;
use yii\base\Component;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Configuration as BaseConfiguration;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt; // ← ADD THIS

class Configuration extends Component
{
   public string $secret = '';

    private BaseConfiguration $config;

    public function init()
    {
        parent::init();

        // Use params or generate fallback
        $this->secret = $this->secret ?: Yii::$app->params['jwtSecret'] ?? bin2hex(random_bytes(64));

        $signer = new Sha256();
        $key = InMemory::plainText($this->secret);

        $this->config = BaseConfiguration::forSymmetricSigner($signer, $key)
            ->withValidationConstraints( 
                new SignedWith($signer, $key), // Signature validation
                new LooseValidAt(SystemClock::fromUTC()) // Time validation (iat, nbf, exp) with 30-sec leeway
            );
    }

    public function getConfig(): BaseConfiguration
    {
        return $this->config;
    }

    // Convenience methods (for easier usage in controllers/middleware)
    public function builder()
    {
        return $this->getConfig()->builder();
    }

    public function parser()
    {
        return $this->getConfig()->parser();
    }

    public function signer()
    {
        return $this->getConfig()->signer();
    }

    public function signingKey()
    {
        return $this->getConfig()->signingKey();
    }

    public function validationConstraints(): array
    {
        return $this->getConfig()->validationConstraints();
    }
}