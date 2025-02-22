<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GoogleController extends AbstractController
{
    use TargetPathTrait;

    #[Route('/connect/google', name: 'connect_google_start')]
    public function connect(ClientRegistry $clientRegistry)
    {
        return $clientRegistry->getClient('google')->redirect(['email', 'profile'], []);
    }


    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(Request $request): Response
    {
        dump($request->query->all()); // Debug xem có code không
        dump($this->getUser()); // Kiểm tra Symfony có nhận diện user không
        die();
    }
    
}
