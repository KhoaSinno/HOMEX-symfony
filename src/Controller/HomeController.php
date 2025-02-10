<?php

namespace App\Controller;

use App\Entity\Specialty;
use App\Repository\SpecialtyRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private EntityManagerInterface $em;
    private SpecialtyRepository $specialtyRepository;
    public function __construct(EntityManagerInterface $em, SpecialtyRepository $specialtyRepository)
    {
        $this->em = $em;
        $this->specialtyRepository = $specialtyRepository;
    }


    #[Route('/', name: 'app_home')]
    public function index(UserRepository $userRepository): Response
    {
        $specialties = $this->specialtyRepository->findAll();
        $doctors = $userRepository->findByRole('ROLE_DOCTOR');

        return $this->render('home/index.html.twig', [
            'specialties' => $specialties,
            'doctors' => $doctors,
        ]);
    }

    #[Route('/search/doctor', name: 'app_search_doctor')]
    public function searchDoctor(UserRepository $userRepository, Request $request): Response
    {
        $specialtyId = $request->query->get('specialty');
        $doctors = $userRepository->findBySpecialty($specialtyId);

        return $this->render('home/search_doctor.html.twig', [
            'doctors' => $doctors,
        ]);
    }
}
