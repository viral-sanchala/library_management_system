<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="BookWiseHistoryResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="book_id", type="integer", example=5),
 *     @OA\Property(property="borrow_user_id", type="integer", example=2),
 *     @OA\Property(property="borrow_date", type="string", format="date-time", example="2025-04-01T12:00:00Z"),
 *     @OA\Property(property="return_date", type="string", format="date-time", example="2025-04-15T12:00:00Z"),
 *     @OA\Property(property="status", type="string", example="B", description="B = Borrowed, R = Returned"),
 *     @OA\Property(
 *         property="user_details",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=2),
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", example="johndoe@example.com")
 *     )
 * )
 */
class BookWiseHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            "id" => $this->id,
            "book_id" => $this->book_id,
            "borrow_date" => $this->borrow_date,
            "status" => $this->status == 'B' ? "Borrowed" : "Returned",
            "return_date" => $this->return_date,
            "user_details" => [
                "id" => $this->user->id,
                "name" => $this->user->name,
                "email" => $this->user->email
            ]
        ];
    }
}
