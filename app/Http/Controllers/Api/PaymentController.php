<?php

namespace App\Http\Controllers\Api;

use Stripe\Stripe;
use Stripe\Webhook;
// use ApiErrorException;
use App\Models\Order;
use App\Models\Payment;
use App\Enum\OrderStatus;
use Stripe\PaymentIntent;
use App\Enum\PaymentStatus;
use Illuminate\Http\Request;
use App\Enum\PaymentProvider;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{
    // inialize the controller with auth middleware
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));


    }

    public function createPayment(Request $request ,Order $order) {

        $request->validate([
            'provider' => 'required|in:stripe,paypal',
        ]);

        if($order->user_id !== $request->user()->id){
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        if (!$order->payment_status == PaymentStatus::PENDING || $order->payment_status == PaymentStatus::FAILED) {
            return response()->json(['message' => 'This order cannot be paid.'], 400);
        }
        
         // check correct payment provider
        $provider = PaymentProvider::from($request->input('provider'));
        if ($provider === PaymentProvider::STRIPE) {
            return $this->createStripePayment($order);
        } else if ($provider === PaymentProvider::PAYPAL) {
              return $this->createPaypalPayment($order);
        } else {
            return response()->json(['message' => 'Unsupported payment provider'], 400);

        }
    }

        protected function createStripePayment(Order $order) {
        // Create a PaymentIntent with the order amount and currency
            try {

                $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'provider' => PaymentProvider::STRIPE,
                'amount' => $order->total,
                'currency' => 'usd',
                'status' => PaymentStatus::PENDING,
                'metadata' => [
                    'order_number' => $order->order_number,
                    'creatred_at' => now()->toIso8601String(),
                ],
            ]);


                // create a payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($order->total * 100), // Stripe expects amount in cents
                'currency' => 'usd',
                'metadata' => [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                ],
                'description' => 'Payment for Order #' . $order->order_number,
            ]);

              // update payment record
            $payment->update([
                'payment_intent_id' => $paymentIntent->id,
                'metadata' => array_merge($payment->metadata, [
                    'client_secret' => $paymentIntent->client_secret,
                ]),
            ]);

                return response()->json([
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_id' => $payment->id,
                'publishable_key' => env('STRIPE_KEY'),
            ]);
            } catch (Exeption $e) {

                Log::error('stripe payment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment intent.',
                'error' => $th->getMessage(),
            ], 500);
            }
    }

        public function ConfirmPayment(Request $request ,$paymentId) {

        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        $payment = Payment::find($paymentId);
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
        if($payment->user_id !== $request->user()->id){
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed successfully.',
            'payment' => $payment,
        ],200);
}

//        }
    public function webhook(Request $request)
{
    $payload        = $request->getContent();
    $sigHeader      = $request->header('Stripe-Signature');
    $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $sigHeader,
            $endpointSecret
        );

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                Log::info('PaymentIntent was successful!');

                $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();

                if ($payment) {
                    $payment->markAsCompleted($paymentIntent->id, [
                        'stripe_data' => [
                            'amount'       => $paymentIntent->amount / 100,
                            'currency'     => $paymentIntent->currency,
                            'status'       => $paymentIntent->status,
                            'description'  => $paymentIntent->description,
                            'completed_at' => now()->toIso8601String(),
                        ],
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment completed successfully.',
                        'payment' => $payment,
                        'order'   => $payment->order,
                    ], 200);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $errorMessage  = $paymentIntent->last_payment_error
                    ? $paymentIntent->last_payment_error->message
                    : 'Unknown error';

                Log::error('Payment failed: ' . $errorMessage);

                $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();

                if ($payment) {
                    $payment->markAsFailed([
                        'stripe_data' => [
                            'amount'        => $paymentIntent->amount / 100,
                            'currency'      => $paymentIntent->currency,
                            'status'        => $paymentIntent->status,
                            'error_message' => $errorMessage,
                            'failed_at'     => now()->toIso8601String(),
                        ],
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment failed and updated.',
                        'payment' => $payment,
                        'order'   => $payment->order,
                    ], 200);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);

            default:
                Log::info('Unhandled event type: ' . $event->type);
                return response()->json([
                    'success' => false,
                    'message' => 'Unhandled event type: ' . $event->type,
                ], 200);
        }
    } catch (\UnexpectedValueException $e) {
        Log::error('Invalid payload: ' . $e->getMessage());
        return response()->json(['error' => 'Invalid payload'], 400);

    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        Log::error('Invalid signature: ' . $e->getMessage());
        return response()->json(['error' => 'Invalid signature'], 400);

    } catch (\Throwable $th) {
        Log::error('Webhook error: ' . $th->getMessage());
        return response()->json(['error' => 'Webhook error'], 500);
    }
}

}
