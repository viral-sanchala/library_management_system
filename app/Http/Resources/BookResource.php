<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="BookResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="The Great Gatsby"),
 *     @OA\Property(property="author", type="string", example="F. Scott Fitzgerald"),
 *     @OA\Property(property="status", type="string", example="Available"),
 *     @OA\Property(property="borrow_user_id", type="integer", example=3, description="The ID of the user who borrowed the book, if any"),
 *     @OA\Property(property="borrow_date", type="string", format="date-time", example="2025-04-01T12:00:00Z"),
 *     @OA\Property(property="return_date", type="string", format="date-time", example="2025-04-15T12:00:00Z")
 * )
 */
class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'details' => $this->details,
            'status' => $this->status
        ];
        if (isAdmin()) {
            if (!empty($this->borrower_details)) {
                $data['borrower_details'] = [
                    "id" => $this->borrower_details->id,
                    "name" => $this->borrower_details->name,
                    "email" => $this->borrower_details->email
                ];
            } else {
                $data['borrower_details'] = [];
            }
        }
        return $data;
    }
}
