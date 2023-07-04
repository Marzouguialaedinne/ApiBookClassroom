<?php

namespace App\EventListener;

use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
	public function __invoke(ExceptionEvent $event)
	{
		$exception = $event->getThrowable();

		$data = [
			"message" => '',
			"status" => ''
		];


		if($exception instanceof NotFoundHttpException ) {

			$data = array_merge($data, [
				"message" => $exception->getMessage(),
				"status" => $exception->getStatusCode(),
			]);
		} else {
			$data = array_merge($data, [
				"message" => $exception->getMessage(),
				"status" => Response::HTTP_INTERNAL_SERVER_ERROR
			]);
		}

		$response = new JsonResponse($data);

		$event->setResponse($response);
	}
}