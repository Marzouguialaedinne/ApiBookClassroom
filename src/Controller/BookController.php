<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class BookController extends AbstractController
{
	public function __construct(
		private BookRepository $bookRepository,
		private AuthorRepository $authorRepository,
		private SerializerInterface $serializer,
		private EntityManagerInterface $entityManager,
		private UrlGeneratorInterface $urlGenerator,
		private ValidatorInterface $validator,
		private TagAwareCacheInterface $cachePool
	)
	{
	}

	#[Route('/api/books', name: 'book', methods: ['GET'])]
	public function index(Request $request): JsonResponse
	{

		$page = $request->get('page', 1);
		$limit = $request->get('limit', 3);

		$idCache = sprintf("getAllBooks-%s-%s", $page, $limit);

		$bookList = $this->cachePool->get($idCache, function (ItemInterface $item) use($page, $limit) {
			$item->tag("cacheAllBook");
			var_dump('dans le cache');
			//$item->expiresAfter(60);
			$books = $this->bookRepository->findAllWithPagination($page, $limit);
			return $this->serializer->serialize($books, 'json', ['groups' => 'getBooks']);
		});

		return new JsonResponse($bookList, 200, [], true);
	}

	#[Route('/api/books/{id}', name: 'details_book', methods: ['GET'])]
	public function getDetailsBook(Book $book): JsonResponse
	{
		$jsonBook = $this->serializer->serialize($book, 'json', ['groups' => 'getBooks']);

		return new JsonResponse($jsonBook, 200, [], true);
	}

	#[Route('/api/books/{id}', name: 'delete_book', methods: ['DELETE'])]
	public function deleteBooks(Book $book): JsonResponse
	{
		$this->cachePool->invalidateTags(["cacheAllBook"]);
		$this->entityManager->remove($book);
		$this->entityManager->flush();

		return new JsonResponse(null, Response::HTTP_NO_CONTENT);
	}

	#[Route('/api/books', name: 'create_book', methods: ['POST'])]
	#[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un livre')]
	public function createBook(Request $request): JsonResponse
	{
		/** @var Book $book */
		$book = $this->serializer->deserialize($request->getContent(), Book::class, 'json');

		$errors = $this->validator->validate($book);

		  if($errors->count() > 0) {
			  //throw new \HttpException(JsonResponse::HTTP_BAD_REQUEST, "La requete est invalide");
			  return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
		  }

		$arrayContent = $request->toArray();
		$book->setAuthor($this->authorRepository->find($arrayContent['authorId']));
		$this->entityManager->persist($book);
		$this->entityManager->flush();

		$jsonContent = $this->serializer->serialize($book, 'json',  ['groups' => 'getBooks']);
		$location = $this->urlGenerator->generate('details_book', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

		return new JsonResponse($jsonContent, Response::HTTP_CREATED, ['Location' => $location], true);
	}

	#[Route('/api/books/{id}', name: 'update_book', methods: ['PUT'])]
	#[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifiér un livre')]
	public function updateBook(Request $request, Book $currentBook): JsonResponse
	{
		/** @var Book $book */
		$book = $this->serializer->deserialize($request->getContent(),
											Book::class,
											'json',
											[AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]
		);

		$arrayContent = $request->toArray();
		$book->setAuthor($this->authorRepository->find($arrayContent['authorId']));

		$this->entityManager->persist($book);
		$this->entityManager->flush();

		$this->cachePool->invalidateTags(["cacheAllBook"]);

		return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
	}

}
