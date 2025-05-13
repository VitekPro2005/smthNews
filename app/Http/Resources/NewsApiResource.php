<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="News",
 * title="News",
 * description="News object structure",
 * @OA\Property(
 * property="id",
 * description="ID новости",
 * type="integer",
 * example=1
 * ),
 * @OA\Property(
 * property="title",
 * description="Заголовок новости",
 * type="string",
 * example="Название новости"
 * ),
 * @OA\Property(
 * property="short_description",
 * description="Краткое описание новости",
 * type="string",
 * example="Короткий текст новости..."
 * ),
 * @OA\Property(
 * property="link",
 * description="Ссылка на источник новости",
 * type="string",
 * format="url",
 * example="https://example.com/source/news/1"
 * ),
 * @OA\Property(
 * property="image_url",
 * description="URL изображения новости",
 * type="string",
 * format="url",
 * example="https://your-domain.com/storage/news_images/image.jpg"
 * ),
 * @OA\Property(
 * property="created_at",
 * description="Дата и время создания новости",
 * type="string",
 * format="date-time",
 * example="2023-10-27 14:30:00"
 * )
 * )
 */
class NewsApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $imageUrl = null;
        if ($this->image && Storage::disk('public')->exists('news_images/' . $this->image)) {
            $imageUrl = Storage::disk('public')->url('news_images/' . $this->image);
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'short_description' => $this->short_description,
            'link' => $this->link,
            'image_url' => $imageUrl,
            'created_at' => $this->created_at->format('Y-m-d H:i:s')
        ];
    }
}
