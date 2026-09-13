<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "New Order — {$this->order->order_number} (KES " . number_format($this->order->total_amount, 2) . ')');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.new-order-alert');
    }
}
