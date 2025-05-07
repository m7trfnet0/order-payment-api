<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * @var PaymentService
     */
    protected $paymentService;

    /**
     * Create a new PaymentController instance.
     *
     * @param PaymentService $paymentService
     * @return void
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->middleware('auth:api');
        $this->paymentService = $paymentService;
    }

    /**
     * Display a listing of the payments.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $orderId = $request->query('order_id');
        $perPage = $request->query('per_page', 15);

        $query = Payment::with('order');
        
        if ($orderId) {
            $query->where('order_id', $orderId);
        } else {
            // If no order_id is specified, only show payments for the user's orders
            $query->whereHas('order', function ($query) {
                $query->where('user_id', Auth::id());
            });
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Process a payment for an order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:credit_card,paypal,bank_transfer,stripe',
            // Additional fields depending on payment method
            'card_number' => 'required_if:payment_method,credit_card',
            'card_expiry_month' => 'required_if:payment_method,credit_card',
            'card_expiry_year' => 'required_if:payment_method,credit_card',
            'card_cvv' => 'required_if:payment_method,credit_card',
            'paypal_email' => 'required_if:payment_method,paypal',
            'account_number' => 'required_if:payment_method,bank_transfer',
            'stripe_token' => 'required_if:payment_method,stripe',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::find($request->order_id);

        // Check if the order belongs to the authenticated user
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. This order does not belong to you.'
            ], 403);
        }

        try {
            // Check if the order is in a state that allows payment processing
            if (!$order->canProcessPayment()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order cannot be processed for payment. Order status must be confirmed.'
                ], 422);
            }

            // Process the payment
            $payment = $this->paymentService->processPayment($order, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified payment.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $payment = Payment::with('order')
            ->whereHas('order', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        // Get live payment status from the gateway
        $status = $this->paymentService->getPaymentStatus($payment);

        return response()->json([
            'success' => true,
            'data' => $payment,
            'gateway_status' => $status
        ]);
    }
}
