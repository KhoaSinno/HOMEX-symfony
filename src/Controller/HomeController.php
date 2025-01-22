<?php

namespace App\Controller;

use App\Entity\Specialty;
use App\Repository\SpecialtyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function index(): Response
    {
        $specialties = $this->specialtyRepository->findAll();
        return $this->render('home/index.html.twig', [
            'specialties' => $specialties
        ]);
    }
}
