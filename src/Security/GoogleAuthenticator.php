<?php

namespace App\Security;

use App\Entity\Cart;
use App\Entity\User;
use App\Entity\Customer;
use App\Entity\Wallet;
use App\Service\RegisterNotifier;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GoogleAuthenticator extends OAuth2Authenticator
{
    private $clientRegistry;
    private $router;
    private $entityManager;
    private $registerNotifier;

    private $urlGenerator;

    use TargetPathTrait;

    public function __construct(ClientRegistry $clientRegistry, RouterInterface $router, EntityManagerInterface $entityManager, RegisterNotifier $registerNotifier, UrlGeneratorInterface $urlGenerator)
    {
        $this->clientRegistry = $clientRegistry;
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->registerNotifier = $registerNotifier;
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        /** @var GoogleUser $googleUser */
        $googleUser = $client->fetchUserFromToken($accessToken);
        $email = $googleUser->getEmail();
        $firstName = $googleUser->getFirstName();
        $lastName = $googleUser->getLastName();

        // Check if user exists **before** creating the Passport
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setRoles(['ROLE_CUSTOMER']);

            $randomPassword = bin2hex(random_bytes(10));
            $user->setPassword($randomPassword);
            $user->setIsVerified(true);

            $customer = new Customer();
            $customer->setUser($user);
            $customer->setFirstName($firstName);
            $customer->setLastName($lastName);

            $wallet = new Wallet();
            $wallet->setCustomer($customer);
            $wallet->setBalance(0.0);
            $wallet->setRewardPoints(0);

            $cart = new Cart();
            $cart->setCustomer($customer);

            $this->entityManager->persist($user);
            $this->entityManager->persist($customer);
            $this->entityManager->persist($wallet);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();

            $this->registerNotifier->sendNewUserNotification($user);
            $this->registerNotifier->sendUserWelcomeEmail($user);

        }

        // Now create the Passport
        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($user) {
                return $user;
            })
        );
    }


    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?RedirectResponse
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $user = $token->getUser();
        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_STAFF', $user->getRoles())) {
            return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
        }

        return new RedirectResponse($this->router->generate('app_shop'));
    }

    public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): ?RedirectResponse
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
