<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="BookHistoryResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="borrow_date", type="string", format="date-time", example="2025-04-01T12:00:00Z"),
 *     @OA\Property(property="status", type="string", example="Borrowed", description="Borrowed or Returned"),
 *     @OA\Property(property="return_date", type="string", format="date-time", nullable=true, example="2025-04-15T12:00:00Z"),
 *     @OA\Property(property="user_id", type="integer", example=2),
 *     @OA\Property(
 *         property="book",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=5),
 *         @OA\Property(property="title", type="string", example="The Great Gatsby"),
 *         @OA\Property(property="details", type="string", example="A novel written by F. Scott Fitzgerald."),
 *         @OA\Property(property="current_status", type="string", example="Available", description="Availability status of the book")
 *     )
 * )
 */
class BookHistoryResource extends JsonResource
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
            'id' => $this->id,
            'borrow_date' => $this->borrow_date,
            'status' => $this->status,
            'return_date' => $this->return_date,
            'user_id' => $this->borrow_user_id,
            'book' => [
                'id' => $this->book->id,
                'title' => $this->book->name,
                'details' => $this->book->details,
                'current_status' => $this->book->status == '0' ? 'Not available' : 'Available'
            ]
        ];
    }
}
