<?php

namespace App\Controller\Api;

use App\Currency\CurrencyService;
use App\Currency\Request\ConvertCurrencyRequest;
use App\Currency\Request\CurrencyRatesRequest;
use Brick\Money\Exception\UnknownCurrencyException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class CurrencyController extends AbstractController
{
    public function __construct(
        private readonly CurrencyService $currencyService,
    ) {
    }

    #[Route('/api/rates', name: 'api/rates', methods: ['GET'])]
    public function index(#[MapQueryString] CurrencyRatesRequest $request): JsonResponse
    {
        try {
            $rates = $this->currencyService->getRates(strtoupper($request->base));
        } catch (UnknownCurrencyException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        return new JsonResponse($rates);
    }

    #[Route('/api/rates/convert', name: 'api/rates/convert', methods: ['GET'])]
    public function convert(#[MapQueryString] ConvertCurrencyRequest $request): JsonResponse
    {
        try {
            $result = $this->currencyService->convert($request->amount, strtoupper($request->from), strtoupper($request->to));
        } catch (UnknownCurrencyException|\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        if (null === $result) {
            return new JsonResponse(['error' => 'Exchange rate not available for this currency pair.'], 422);
        }

        return new JsonResponse($result);
    }
}
