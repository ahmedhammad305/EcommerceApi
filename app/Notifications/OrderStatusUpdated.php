<?php
// app/Notifications/OrderStatusUpdated.php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderStatusUpdated extends Notification
{
    use Queueable; 

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * تحديد قنوات التسليم: البريد الإلكتروني فقط.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('تم تحديث حالة طلبك - رقم ' . $this->order->id)
                    ->greeting("مرحباً {$notifiable->name}")
                    // ->line("تم تحديث حالة طلبك إلى: **{$this->order->status}**.")
                    ->action('مشاهدة تفاصيل الطلب', url('/api/orders/' . $this->order->id))
                    ->line('شكراً لك.');
    }
}
