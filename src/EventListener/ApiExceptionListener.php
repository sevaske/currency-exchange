<?php

namespace App\EventListener;

use App\Currency\Exception\CurrencyStorageException;
use Brick\Money\Exception\UnknownCurrencyException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        if ($this->isValidationException($exception)) {
            $errors = [];

            foreach ($exception->getPrevious()->getViolations() as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            $event->setResponse(new JsonResponse(['errors' => $errors], $exception->getStatusCode()));

            return;
        }

        if ($exception instanceof ValidationFailedException) {
            $errors = [];

            foreach ($exception->getViolations() as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            $event->setResponse(new JsonResponse(['errors' => $errors], 422));

            return;
        }

        $response = match (true) {
            $exception instanceof UnknownCurrencyException => new JsonResponse([
                'error' => $exception->getMessage(),
            ], 400),
            $exception instanceof CurrencyStorageException => new JsonResponse([
                'error' => Response::$statusTexts[503],
            ], 503),
            default => new JsonResponse([
                'error' => Response::$statusTexts[500],
            ], 500),
        };

        $event->setResponse($response);
    }

    private function isValidationException(Throwable $exception): bool
    {
        if ($exception instanceof ValidationFailedException) {
            return true;
        }

        if (! $exception instanceof UnprocessableEntityHttpException) {
            return false;
        }

        if ($exception->getPrevious() instanceof ValidationFailedException) {
            return true;
        }

        return false;
    }
}
