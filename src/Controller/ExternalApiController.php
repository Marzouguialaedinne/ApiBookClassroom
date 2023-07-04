<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExternalApiController extends AbstractController
{
    #[Route('/api/external/getSfDoc', name: 'app_external_api', methods: ['GET'])]
    public function getSymfonyDoc(HttpClientInterface $httpClient): JsonResponse
    {
		$response = $httpClient->request(
			'GET',
			'https://api.github.com/repos/symfony/symfony-docs'
		);

		return new JsonResponse($response->getContent(), $response->getStatusCode(), [], true);
    }
}
