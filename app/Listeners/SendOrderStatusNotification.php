<?php
namespace App\Listeners;

use App\Events\OrderStatusUpdated;

use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\OrderStatusUpdated as OrderStatusUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendOrderStatusNotification implements ShouldQueue
{
    use InteractsWithQueue; 

    /**
     * معالجة الحدث.
     */
    public function handle(OrderStatusUpdated $event): void
    {

        $user = $event->order->user;

        if (!$user || empty($user->email)) {
             Log::warning("Queue Job Failed: Order ID {$event->order->id} has no valid user or email address. Skipping notification.");
             return;
        }

        $user->notify(new OrderStatusUpdatedNotification($event->order));
    }
}
