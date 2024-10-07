<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'app_all_books', methods: ['GET'])]
    public function getAllBooks(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $bookList = $bookRepository->findAll();
        $jsonBookList = $serializer->serialize($bookList, 'json');

        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true); //les données sérialisées, le code retour : ici Response::HTTP_OK  correspond au code 200, les headers, un true qui signifie que nous avons déjà sérialisé les données et qu’il n’y a donc plus de traitement à faire dessus.
    }

    #[Route('/api/books/{id}', name: 'app_detail_book', methods: ['GET'])]
    public function getDetailBook($id, BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $book = $bookRepository->find($id);
        if ($book) {
            $jsonBook = $serializer->serialize($book, 'json');

            return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND); // une réponse vide avec un autre code d’erreur :  Reponse::HTTP_NOT_FOUND , erreur 404
    }
}