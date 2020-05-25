<?php

namespace App\Auth;

use App\Repositories\Auth\User\UserRepository;
use Coderello\SocialGrant\Resolvers\SocialUserResolverInterface;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Socialite\Facades\Socialite;
use League\OAuth2\Server\Exception\OAuthServerException;

class PassportSocialResolver implements SocialUserResolverInterface
{
    /**
     * @var \App\Repositories\Auth\User\UserRepository
     */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param  string  $provider
     * @param  string  $accessToken
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function resolveUserByProviderCredentials(string $provider, string $accessToken): ?Authenticatable
    {
        // Return the user that corresponds to provided credentials.
        // If the credentials are invalid, then return NULL.
        if (config("services.{$provider}.active", false) === false) {
            throw OAuthServerException::invalidRequest('provider');
        }

        $providerUser = null;
        try {
            $providerUser = Socialite::driver($provider)
                ->fields(
                    [
                        'name',
                        'first_name',
                        'last_name',
                        'email',
                    ]
                )
                ->stateless()->userFromToken($accessToken);
        } catch (Exception $exception) {
        }

        if (blank($providerUser)) {
            return null;
        }

        return $this->userRepository->findOrCreateProvider($providerUser, $provider);
    }
}
