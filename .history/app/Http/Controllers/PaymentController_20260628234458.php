<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;


class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    // ─── POST /api/payments ───────────────────────────────────────────────────
    /**
     * Process a new payment for a confirmed order.
     */
    public function store(ProcessPaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->processPayment($request->validated());

            $statusCode = $payment->isSuccessful() ? 201 : 422;

            return response()->json([
                'success' => $payment->isSuccessful(),
                'message' => $payment->isSuccessful()
                    ? 'Payment processed successfully.'
                    : 'Payment processing failed.',
                'data'    => new PaymentResource($payment),
            ], $statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 422);
        }
    }

    // ─── GET /api/payments ────────────────────────────────────────────────────
    /**
     * Retrieve all payments.
     */
    public function index(): JsonResponse
    {
        $payments = $this->paymentService->getAllPayments();

        return response()->json([
            'success' => true,
            'message' => 'Payments retrieved successfully.',
            'data'    => PaymentResource::collection($payments),
            'meta'    => [
                'total'      => $payments->count(),
                'successful' => $payments->where('status', 'successful')->count(),
                'failed'     => $payments->where('status', 'failed')->count(),
                'pending'    => $payments->where('status', 'pending')->count(),
            ],
        ]);
    }

    // ─── GET /api/payments/{paymentId} ────────────────────────────────────────
    /**
     * Retrieve a single payment by its payment_id (e.g., PAY-XXXX).
     */
    public function show(string $paymentId): JsonResponse
    {
        dd('idsfa');
        try {
            $payment = $this->paymentService->getPaymentById($paymentId);

            return response()->json([
                'success' => true,
                'message' => 'Payment retrieved successfully.',
                'data'    => new PaymentResource($payment),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => "Payment '{$paymentId}' not found.",
                'data'    => null,
            ], 404);
        }
    }

    // ─── GET /api/orders/{orderId}/payments ───────────────────────────────────
    /**
     * Retrieve all payments for a specific order.
     */
    public function byOrder(int $orderId): JsonResponse
    {
        try {
            $payments = $this->paymentService->getPaymentsByOrder($orderId);

            return response()->json([
                'success' => true,
                'message' => "Payments for order #{$orderId} retrieved successfully.",
                'data'    => PaymentResource::collection($payments),
                'meta'    => [
                    'order_id' => $orderId,
                    'total'    => $payments->count(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => "Order #{$orderId} not found.",
                'data'    => null,
            ], 404);
        }
    }

    // ─── GET /api/payments/gateways ───────────────────────────────────────────
    /**
     * List all available payment gateways.
     */
    public function gateways(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Available payment gateways.',
            'data'    => $this->paymentService->getAvailableGateways(),
        ]);
    }
}
