<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Category;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'price' => (float) $this->price,
            'description' => $this->when(
                $this->isDetailRoute(),
                $this->description
            ),
            'category' => $this->whenLoaded('category'),
            'publisher' => $this->publisher,
            'publication_date' => $this->publication_date,
            'language' => $this->language,
            'format' => $this->format,
            'page_count' => $this->page_count,
            'rating' => $this->rating,
            'stock_quantity' => $this->stock_quantity,
            'cover_image' => $this->cover_image,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Check if the current route is the book detail route
     */
    protected function isDetailRoute(): bool
    {
        return request()->routeIs('books.show');
    }

    /**
     * Check if a relationship is already loaded
     */
    protected function whenLoaded(string $relationship)
    {
        return $this->resource->relationLoaded($relationship) 
            ? $this->resource->{$relationship}
            : null;
    }

    /**
     * Transform category resource with safe loading
     */
    protected function transformCategory($category): ?array
    {
        if (!$category) {
            return null;
        }

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ];
    }
}
