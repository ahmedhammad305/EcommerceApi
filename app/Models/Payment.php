<?php

namespace App\Models;

use App\Models\User;
use App\Models\Order;
use App\Enum\PaymentStatus;
use App\Enum\PaymentProvider;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];

      protected $casts = [
        'metadata' => 'array',
        'completed_at' => 'datetime',
        'amount' => 'decimal:2',
        'provider' => PaymentProvider::class,
        'status' => PaymentStatus::class,
    ];


    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

       public function markAsCompleted($paymentIntentId, $metadata = [])
    {
      
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'payment_intent_id' => $paymentIntentId,
            'completed_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], $metadata)

        ]);

        $this->order->markAsPaid($paymentIntentId);
    }

    // mark as completed for PayPal
    public function markAsCompletedPayPal(string $paypalCaptureId, array $metadata = [])
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'paypal_capture_id' => $paypalCaptureId,
            'completed_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], $metadata)
        ]);

        $this->order->markAsPaid($paypalCaptureId);
    }
    // mark as failed
       public function markAsFailed($metadata = [])
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'metadata' => array_merge($this->metadata ?? [], $metadata)
        ]);

        $this->order->markAsFailed();
    }
}
