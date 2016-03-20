<?php

namespace Guigzz\Wsse;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Guigzz\Wsse\WsseUserToken;

class WsseProvider implements AuthenticationProviderInterface
{
    private $userProvider;
    private $cacheDir;
    private $timeWindow;

    public function __construct(UserProviderInterface $userProvider, $cacheDir, $timeWindow)
    {
        $this->userProvider = $userProvider;
        $this->cacheDir     = $cacheDir;
        $this->timeWindow = $timeWindow;
    }

    public function authenticate(TokenInterface $token)
    {
        $user = $this->userProvider->loadUserByUsername($token->getUsername());
        
        if ($user && $this->validateDigest($token->digest, $token->nonce, $token->created, $user->getPassword())) {
            $authenticatedToken = new WsseUserToken($user->getRoles());
            $authenticatedToken->setUser($user);

            return $authenticatedToken;
        }
        
        throw new AuthenticationException('The WSSE authentication failed.');
    }

    /**
     * 
     *
     * For more information specific to the logic here, see
     * https://github.com/symfony/symfony-docs/pull/3134#issuecomment-27699129
     */
    protected function validateDigest($digest, $nonce, $created, $secret)
    {
        // Check created time is not in the future
        if (strtotime($created) > time()) {
            return false;
        }

        // Expire timestamp after "timeWindow" seconds
        if (time() - strtotime($created) > $this->timeWindow) {
            return false;
        }

        // Validate that the nonce is *not* used in the last "timeWindow" seconds
        // if it has, this could be a replay attack
        if (
            file_exists($this->cacheDir.'/'.md5($nonce))
            && file_get_contents($this->cacheDir.'/'.md5($nonce)) + $this->timeWindow > time()
        ) {
            throw new NonceExpiredException('Previously used nonce detected');
        }
        // If cache directory does not exist we create it
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
        file_put_contents($this->cacheDir.'/'.md5($nonce), time());

        // Validate Secret
        $expected = base64_encode(sha1(base64_decode($nonce).$created.$secret, true));

        return hash_equals($expected, $digest);
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof WsseUserToken;
    }
}

