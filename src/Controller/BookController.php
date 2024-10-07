<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'app_book', methods: ['GET'])]
    public function getAllBooks(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/BookController.php',
        ]);
    }
}