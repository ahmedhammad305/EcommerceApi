<?php

namespace App\Models;

use App\Models\User;
use App\Models\Payment;
use App\Enum\OrderStatus;
use App\Models\OrderItem;
use App\Enum\PaymentStatus;
use App\Models\OrderMangement;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
        protected $guarded = [];

           protected $casts = [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
    ];

        public function user()
        {
            return $this->belongsTo(User::class);
        }

        public function items()
        {
            return $this->hasMany(OrderItem::class);
        }

        public function payment()
        {
            return $this->hasOne(Payment::class);
        }

    public function statusHistory()
    {
        return $this->hasMany(OrderMangement::class);
    }

      public function transitionTo(OrderStatus $newStatus, ?string $notes = null): bool
    {
        if ($this->status === $newStatus) {
            return true;
        }

        if (!$this->status->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->status;

        // تحديث حالة الطلب
        $this->update(['status' => $newStatus]);

        // تسجيل تاريخ الحالة
        $this->statusHistory()->create([
            'order_id' => $this->id,
            'from_status' => $oldStatus->value,
            'to_status' => $newStatus->value,
            'note' => $notes,
        ]);



        return true;
    }

    // get allowed transitions for the current status
    public function getAllowedTransitions(): array
    {
        return $this->status->getAllowedTransitions();
    }

    public function getLatestStatusChange()
    {
        return $this->statusHistory()->first();
    }

    // generate a unique order number
    public static function generateOrderNumber()
    {
        $year = date('Y');


        $randomNumber = strtoupper(substr(uniqid(), -6));
        return "ORD-{$year}-{$randomNumber}"; // e.g., ORD-2025-ABC123
    }

    public function canBeCancelled()
    {
        return in_array($this->status, [
            OrderStatus::PENDING,
            OrderStatus::PAID,
        ]);
    }

    // mark as paid
    public function markAsPaid($transactionId)
    {
        $this->update([
            'status' => OrderStatus::PAID,
            'payment_status' => PaymentStatus::COMPLETED,
            'transaction_id' => $transactionId,
            'paid_at' => now(),
        ]);
    }

    // mark as faild
    public function markAsFailed()
    {
        $this->update([
            'payment_status' => PaymentStatus::FAILED,
        ]);
    }

    /**
     * Check if the order can accept a payment
     *
     * @return bool
     */
    public function canAcceptPayment(): bool
    {
        return $this->payment_status === PaymentStatus::PENDING ||
            $this->payment_status === PaymentStatus::FAILED;
    }

}
