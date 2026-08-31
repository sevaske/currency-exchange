<?php

namespace App\Controller\Api;

use App\Currency\CurrencyService;
use App\Currency\Request\ConvertCurrencyRequest;
use App\Currency\Request\CurrencyRatesRequest;
use Brick\Math\Exception\MathException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CurrencyController extends AbstractController
{
    public function __construct(
        private readonly CurrencyService $currencyService,
    ) {
    }

    /**
     * @throws MathException
     */
    #[Route('/api/rates', methods: ['GET'], format: 'json')]
    public function index(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        CurrencyRatesRequest $request
    ): JsonResponse
    {
        return $this->json($this->currencyService->getRates($request->base));
    }

    /**
     * @throws MathException
     */
    #[Route('/api/rates/convert', methods: ['GET'], format: 'json')]
    public function convert(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ConvertCurrencyRequest $request,
    ): JsonResponse
    {
        return $this->json(
            $this->currencyService->convert(
                amount: $request->amount,
                fromCurrency: strtoupper($request->from),
                toCurrency: strtoupper($request->to),
            ),
        );
    }
}
