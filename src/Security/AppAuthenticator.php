<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use App\Entity\User;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $formData = $request->getPayload()->all();
        $email = $formData['login_form']['email'] ?? '';

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($formData['login_form']['password'] ?? ''),
            [
                new CsrfTokenBadge('authenticate', $formData['login_form']['_token'] ?? ''),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Role-based redirect logic
        $user = $token->getUser();
        
        if ($user instanceof User) {
            $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
            
            if ($targetPath) {
                return new RedirectResponse($targetPath);
            }

            // Redirect based on user role
            switch ($user->getRole()) {
                case 1: // Admin
                    return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
                case 2: // Department Head
                    return new RedirectResponse($this->urlGenerator->generate('department_head_dashboard'));
                case 3: // Faculty
                    return new RedirectResponse($this->urlGenerator->generate('faculty_dashboard'));
                default:
                    return new RedirectResponse($this->urlGenerator->generate('app_home'));
            }
        }

        // If something went wrong, redirect to home
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}