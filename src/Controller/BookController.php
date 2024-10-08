<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
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

class BookController extends AbstractController
{
    /**
     * Méthode pour récupérer tous les livres
     */
    #[Route('/api/books', name: 'app_all_books', methods: ['GET'])]
    public function getAllBooks(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $bookList = $bookRepository->findAll();

        //On convertit une instance d'un objet en une représentation de données (comme JSON).
        $jsonBookList = $serializer->serialize($bookList, 'json', ['groups' => 'getBooks']); // 1) Les données à transformer, 2) Le format des données d'entrée, 3) Contexte : spécifie que seules les propriétés annotées avec le groupe getBooks dans les entités doivent être sérialisées.

        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true); //les données sérialisées, le code retour : ici Response::HTTP_OK  correspond au code 200, les headers, un true qui signifie que nous avons déjà sérialisé les données et qu’il n’y a donc plus de traitement à faire dessus.
    }

    /**
     * Méthode pour récupérer le détail d'un livre
     */
    #[Route('/api/books/{id}', name: 'app_detail_book', methods: ['GET'])]
    public function getDetailBook($id, BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $book = $bookRepository->find($id);
        if ($book) {
            $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']); // On indique quel groupe on veut récupérer

            return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND); // une réponse vide avec un autre code d’erreur :  Reponse::HTTP_NOT_FOUND , erreur 404
    }

    /**
     * Méthode pour supprimer un livre
     */
    #[Route('/api/books/{id}', name: 'app_deleteBook', methods: ['DELETE'])]
    public function deleteBook($id, BookRepository $bookRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // On récupère le livre à supprimer.
        $book = $bookRepository->find($id);

        $entityManager->remove($book);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Méthode pour ajouter un livre
     */
    #[Route('/api/books', name: "app_createBook", methods: ['POST'])]
    public function addBook(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        AuthorRepository $authorRepository,
        ValidatorInterface $validator
    ): JsonResponse {

        $jsonBook = $request->getContent();

        // Un nouvel objet de la classe Book est créé.
        $book = $serializer->deserialize(
            $jsonBook,
            Book::class, // Indique que les données JSON seront mappées à une instance de la classe Book. Le Serializer utilise les données JSON pour "peupler" les propriétés de l'objet cible.
            'json'
        );

        // Récupération de l'ensemble des données envoyées sous forme de tableau
        $content = $request->toArray();

        // Récupération de l'idAuthor. S'il n'est pas défini, alors on met -1 par défaut.
        $idAuthor = $content['idAuthor'] ?? -1;

        // On cherche l'auteur qui correspond et on l'assigne au livre.
        // Si "find" ne trouve pas l'auteur, alors null sera retourné.
        $book->setAuthor($authorRepository->find($idAuthor));

        // On vérifie les erreurs
        $errors = $validator->validate($book);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            //throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "La requête est invalide");
        }

        $entityManager->persist($book);
        $entityManager->flush();

        $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);

        // On générere la route qui pourrait être utilisée pour récupérer des informations sur le livre créé.
        $location = $urlGenerator->generate('app_detail_book', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ["Location" => $location], true); // On place la route générée dans le header. Cette URL pourrait être interrogée si on voulait avoir plus d’informations sur l’élément créé.
    }

    /**
     * Méthode pour mettre à jour un livre.
     */
    #[Route('/api/books/{id}', name: "app_updateBook", methods: ['PUT'])]
    public function updateBook(
        $id,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        AuthorRepository $authorRepository,
        BookRepository $bookRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        $currentBook = $bookRepository->find($id);

        $updatedBook = $serializer->deserialize(
            $request->getContent(), // $data
            Book::class, // $type
            'json', // $format
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook] // $context : tableau associatif qui fournit des informations supplémentaires pour la désérialisation. Cela permet de passer des options de contexte qui influencent le comportement du processus de désérialisation. En l'occurence, elle indique que l'on veut mettre à jour un objet existant (plutôt que d'en créer un nouveau à partir de zéro).
        );

        // On vérifie les erreurs
        $errors = $validator->validate($updatedBook);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;
        $updatedBook->setAuthor($authorRepository->find($idAuthor));

        $em->persist($updatedBook);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}