<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class LowStockAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Collection $products Products currently at/below their low_stock_threshold
     * @param string $type 'realtime' (single product just crossed) or 'digest' (daily roundup)
     */
    public function __construct(
        public Collection $products,
        public string $type = 'digest'
    ) {}

    public function envelope()
    {
        $subject = $this->type === 'realtime'
            ? "Low Stock Alert: {$this->products->first()->name}"
            : 'SokoTrack Daily Low Stock Digest — ' . $this->products->count() . ' item(s) need attention';

        return new \Illuminate\Mail\Mailables\Envelope(subject: $subject);
    }

    public function content()
    {
        return new \Illuminate\Mail\Mailables\Content(
            markdown: 'emails.low-stock-alert',
        );
    }
}
