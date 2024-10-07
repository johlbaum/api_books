<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $authorList = [];

        for ($i = 0; $i < 10; $i++) {
            $author = new Author();
            $author->setLastName('Nom' . $i);
            $author->setFirstName('Prénom' . $i);
            $manager->persist($author);

            $authorList[] = $author;
        }

        for ($i = 0; $i < 20; $i++) {
            $book = new Book();
            $book->setTitle('Titre' . $i);
            $book->setCoverText('Cover' . $i);

            // On lie le livre à un auteur pris au hasard dans le tableau des auteurs.
            $book->setAuthor($authorList[array_rand($authorList)]);

            $manager->persist($book);
        }

        $manager->flush();
    }
}