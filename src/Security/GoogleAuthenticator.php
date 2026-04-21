<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\Staff;
use App\Service\RegisterNotifier;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
    private $passwordHasher;


    use TargetPathTrait;

    public function __construct(ClientRegistry $clientRegistry, RouterInterface $router, EntityManagerInterface $entityManager, RegisterNotifier $registerNotifier, UrlGeneratorInterface $urlGenerator, UserPasswordHasherInterface $passwordHasher)
    {
        $this->clientRegistry = $clientRegistry;
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->registerNotifier = $registerNotifier;
        $this->urlGenerator = $urlGenerator;
        $this->passwordHasher = $passwordHasher;
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();

                // 1. Check if user already exists
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                if (!$user) {
                    // 2. Create new user specifically as Staff (except for the configured admin)
                    $user = new User();
                    $user->setEmail($email);
                    // Use a high-entropy random password
                    $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));

                    // Assign Roles based on email
                    if ($email === $_ENV['ADMIN_EMAIL']) {
                        $user->setRoles(['ROLE_SUPER_ADMIN']);
                        
                        // Link Admin profile
                        $admin = new Admin();
                        $admin->setFirstName($googleUser->getFirstName());
                        $admin->setLastName($googleUser->getLastName());
                        $admin->setUser($user);
                        $this->entityManager->persist($admin);
                    } else {
                        // All other new Google logins are Staff by default for this flow
                        $user->setRoles(['ROLE_STAFF']);
                        
                        // Link Staff profile
                        $staff = new Staff();
                        $staff->setFirstName($googleUser->getFirstName());
                        $staff->setLastName($googleUser->getLastName());
                        $staff->setUser($user);
                        $this->entityManager->persist($staff);
                    }

                    $this->entityManager->persist($user);
                }

                // 3. Mandatory Automatic Verification for Google Sign-ins
                $user->setIsVerified(true);
                $this->entityManager->flush();

                return $user;
            }),
            [
                // 4. Persistence ensured via RememberMeBadge
                new RememberMeBadge(),
            ]
        );
    }


    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?RedirectResponse
    {
        if ($request->hasSession()) {
            $request->getSession()->getFlashBag()->add('success', 'Successfully logged in with Google!');
        }

        // Get the authenticated user from the token
        /** @var User $user */
        $user = $token->getUser();
        $roles = $user->getRoles();

        if (\in_array('ROLE_SUPER_ADMIN', $roles, true) || \in_array('ROLE_ADMIN', $roles, true) || \in_array('ROLE_STAFF', $roles, true)) {
            return new RedirectResponse($this->router->generate('app_dashboard'));
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }else {
            return new RedirectResponse($this->router->generate('app_shop'));
        }
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        // Pass the error back to your custom login template
        $request->getSession()->getFlashBag()->add('error', $message);
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
