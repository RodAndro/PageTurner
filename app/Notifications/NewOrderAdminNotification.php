<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderAdminNotification extends Notification
{
    use Queueable;

    public $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $itemCount = $this->order->orderItems->count();
        
        return (new MailMessage)
            ->subject('New Order Received - Order #' . $this->order->id)
            ->greeting('Hello Admin!')
            ->line('A new order has been placed and requires your attention.')
            ->line('**Order Details:**')
            ->line('Order Number: #' . $this->order->id)
            ->line('Customer: ' . $this->order->user->name)
            ->line('Total Amount: $' . number_format($this->order->total_amount, 2))
            ->line('Items: ' . $itemCount . ' item(s)')
            ->line('Status: ' . ucfirst($this->order->status))
            ->action('View Order Details', url('/customer_orders'))
            ->line('Please process this order promptly.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'customer_name' => $this->order->user->name,
            'total_amount' => $this->order->total_amount,
        ];
    }
}
