<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthorController extends AbstractController
{
    /**
     * Méthode pour récupérer tous les auteurs
     */
    #[Route('/api/authors', name: 'app_authors', methods: ['GET'])]
    public function getAuthors(AuthorRepository $authorRepository, SerializerInterface $serializer): JsonResponse
    {
        $authorsList = $authorRepository->findAll();

        $jsonAuthors = $serializer->serialize($authorsList, 'json', ['groups' => 'getAuthors']);

        return new JsonResponse($jsonAuthors, Response::HTTP_OK, [], true); // Response : méthode static (pas besoin d'instancier la classe)
    }

    /**
     * Méthode pour récupérer le détail d'un auteur
     */
    #[Route('/api/authors/{id}', name: 'app_detail_author', methods: ['GET'])]
    public function getAuthor($id, AuthorRepository $authorRepository, SerializerInterface $serializer): JsonResponse
    {
        $author = $authorRepository->find($id);
        if ($author) {
            $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => 'getAuthors']);

            return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    /**
     * Méthode pour supprimer un auteur
     */
    #[Route('/api/authors/{id}', name: 'app_delete_author', methods: ['DELETE'])]
    public function deleteAuthor($id, AuthorRepository $authorRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $author = $authorRepository->find($id);
        $entityManager->remove($author);

        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Méthode pour ajouter un auteur
     */
    #[Route('/api/authors', name: 'app_create_author', methods: ['POST'])]
    public function createAuthor(
        SerializerInterface $serializerInterface,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        Request $request,
        ValidatorInterface $validator
    ): JsonResponse {
        $jsonAuthor = $request->getContent();

        // Création d'un nouvel objet
        $author = $serializerInterface->deserialize($jsonAuthor, Author::class, 'json');

        // On vérifie les erreurs
        $errors = $validator->validate($author);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($author);
        $entityManager->flush();

        // On générere la route qui pourrait être utilisée pour récupérer des informations sur l'auteur créé.
        $location = $urlGenerator->generate('app_detail_author', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $jsonAuthor = $serializerInterface->serialize($author, 'json', ['groups' => 'getAuthors']);

        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * Méthode pour mettre à jour un auteur
     */
    #[Route('api/authors/{id}', name: 'app_updateAuthor', methods: ['PUT'])]
    public function updateAuthor(
        $id,
        Request $request,
        SerializerInterface $serializerInterface,
        AuthorRepository $authorRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        // Récupération de l'objet en base de données.
        $currentAuthor = $authorRepository->find($id);

        // Récupération du contenu de la requête.
        $jsonAuthor = $request->getContent();

        // Création d'un nouvel objet.
        $updatedAuthor = $serializerInterface->deserialize(
            $jsonAuthor,
            Author::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor] // $context : tableau associatif qui fournit des informations supplémentaires pour la désérialisation. Cela permet de passer des options de contexte qui influencent le comportement du processus de désérialisation. En l'occurence, elle indique que l'on veut mettre à jour un objet existant (plutôt que d'en créer un nouveau à partir de zéro).
        );

        // On vérifie les erreurs
        $errors = $validator->validate($currentAuthor);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($updatedAuthor);
        $entityManager->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}