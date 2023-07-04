<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
	public function __construct(private UserPasswordHasherInterface $userPasswordHasher){}

	public function load(ObjectManager $manager): void
    {
		$userSimple = new User();
		$userSimple->setEmail('simple@gmail.com');
		$userSimple->setRoles(['ROLE_USER']);
		$userSimple->setPassword($this->userPasswordHasher->hashPassword($userSimple, 'password'));
		$manager->persist($userSimple);

	    $userAdmin = new User();
	    $userAdmin->setEmail('admin@gmail.com');
	    $userAdmin->setRoles(['ROLE_ADMIN']);
	    $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, 'password'));
	    $manager->persist($userAdmin);

		$listAuthour = [];

		for ($i= 1; $i <= 10; $i++) {
			$author = new Author();
			$author->setFirstname(sprintf("Firstname %s ", $i));
			$author->setLastname(sprintf("Lastname %s ", $i));
			$manager->persist($author);
			$listAuthour[] = $author;
		}
		for ($i = 1; $i <= 20; $i++) {
			$book = new Book();
			$book->setTitle(sprintf("Livre numéro : %s", $i));
			$book->setCoverText(sprintf("Quatrième de couverture numéro : %s", $i));

			$book->setAuthor($listAuthour[array_rand($listAuthour)]);
			$manager->persist($book);
		}

        $manager->flush();
    }
}
