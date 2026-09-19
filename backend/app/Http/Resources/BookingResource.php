<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Booking shape. Contact fields (encrypted at rest) are included only when
 * the requester may view the booking (owning vendor or admin) - guests get
 * the fulfilment view without anyone's contact data.
 */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $includeContact = auth()->check()
            && auth()->user()->can('view', $this->resource);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'vendor' => $this->whenLoaded('vendor', fn (): ?array => $this->vendor === null ? null : [
                'id' => $this->vendor->id,
                'display_name' => $this->vendor->display_name,
            ]),
            'items' => BookingItemResource::collection($this->whenLoaded('items')),
            'is_reseller' => $this->is_reseller,
            'notes' => $this->notes,
            'settled_offline' => $this->settled_offline,
            'contact' => $this->when($includeContact, fn (): array => [
                'name' => $this->contact_name,
                'phone' => $this->contact_phone,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
