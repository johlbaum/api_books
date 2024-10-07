<?php

namespace App\Controller;

use App\Repository\AuthorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class AuthorController extends AbstractController
{
    #[Route('/api/authors', name: 'app_authors', methods: ['GET'])]
    public function getAuthors(AuthorRepository $authorRepository, SerializerInterface $serializer): JsonResponse
    {
        $authorsList = $authorRepository->findAll();

        $jsonAuthors = $serializer->serialize($authorsList, 'json', ['groups' => 'getAuthors']);

        return new JsonResponse($jsonAuthors, Response::HTTP_OK, [], true);
    }

    #[Route('/api/authors/{id}', name: 'app_author', methods: ['GET'])]
    public function getAuthor($id, AuthorRepository $authorRepository, SerializerInterface $serializer): JsonResponse
    {
        $author = $authorRepository->find($id);
        if ($author) {
            $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => 'getAuthors']);

            return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}