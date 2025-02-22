<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\NoCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Routing\RouterInterface;

use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\SelfValidatingCredentials;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleAuthenticator extends AbstractAuthenticator
{
    private EntityManagerInterface $entityManager;
    private GoogleClient $googleClient;
    private RouterInterface $router;

    public function __construct(EntityManagerInterface $entityManager, GoogleClient $googleClient, RouterInterface $router)
    {
        $this->entityManager = $entityManager;
        $this->googleClient = $googleClient;
        $this->router = $router;
    }

    public function supports(Request $request): ?bool
    {
        // dump($request->attributes->all()); // Debug xem request có gì
        // dump($request->query->all()); // Debug query parameters
        // die();

        return $request->attributes->get('_route') === 'connect_google_check' && $request->query->has('code');
    }




    public function authenticate(Request $request): Passport
{
    $query = $request->query->all();
    // dump($query); // Debug xem có code không
    // die();

    if (!isset($query['code'])) {
        throw new \LogicException('Google OAuth không trả về code.');
    }

    // Bước 1: Lấy access token từ Google
    $httpClient = HttpClient::create();
    $response = $httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
        'body' => [
            'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
            'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
            'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'],
            'grant_type' => 'authorization_code',
            'code' => $query['code'],
        ],
    ]);

    $data = $response->toArray();
    // dump($data); // Kiểm tra access_token
    // die();

    // Bước 2: Lấy thông tin user từ Google
    $userResponse = $httpClient->request('GET', 'https://www.googleapis.com/oauth2/v1/userinfo', [
        'headers' => [
            'Authorization' => 'Bearer ' . $data['access_token'],
        ],
    ]);

    $userData = $userResponse->toArray();
    // dump($userData); // Debug thông tin user
    // die();

   // 1. Kiểm tra xem user đã tồn tại trong database chưa
   $userRepository = $this->entityManager->getRepository(User::class);
   $user = $userRepository->findOneBy(['email' => $userData['email']]);

   // 2. Nếu user chưa tồn tại, tạo user mới
   if (!$user) {
       $user = new User();
       $user->setEmail($userData['email']);
       $user->setFullName($userData['name']); // Hoặc dùng $userData['given_name'] và $userData['family_name']
       $user->setGoogleId($userData['id']);
       $user->setImage($userData['picture']);
       $user->setRoles(['ROLE_PATIENT']); // Mặc định phân quyền USER

       // Lưu user vào database
       $this->entityManager->persist($user);
       $this->entityManager->flush();
   }

   return new SelfValidatingPassport(new UserBadge($user->getEmail()));
}




    private function getOrCreateUser(GoogleUser $googleUser): UserInterface
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $googleUser->getEmail()]);

        if (!$user) {
            $user = new User();
            $user->setEmail($googleUser->getEmail());
            $user->setPassword('');
            $user->setRoles(['ROLE_USER']);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $user;
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?RedirectResponse
    {
        return new RedirectResponse($this->router->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, \Throwable $exception): ?RedirectResponse
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
