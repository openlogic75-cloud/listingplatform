<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->product_id,
            'title' => $this->whenLoaded('product', fn (): ?string => $this->product?->title),
            'quantity' => $this->quantity,
            'unit' => $this->whenLoaded('product', fn (): ?string => $this->product?->unit),
            'moq' => $this->whenLoaded('product', fn (): ?int => $this->product?->moq),
            'unit_price_snapshot' => $this->unit_price_snapshot !== null
                ? (float) $this->unit_price_snapshot
                : null,
        ];
    }
}
