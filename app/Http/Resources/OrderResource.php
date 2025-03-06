<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'total' => $this->total,
            'status' => $this->status,
            'created_at' => $this->created_at->toDateTimeString(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'history' => OrderHistoryResource::collection($this->whenLoaded('history')),
        ];
    }
}
