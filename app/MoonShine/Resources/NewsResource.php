<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Services\NewsImageService;
use Illuminate\Database\Eloquent\Model;
use App\Models\News;

use Illuminate\Support\Facades\Storage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Attributes\Icon;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Url;
use Illuminate\Support\Facades\Log;


#[Icon('newspaper')]

/**
 * @extends ModelResource<News>
 */
class NewsResource extends ModelResource
{
    protected string $model = News::class;

    protected string $title = 'News';

    protected string $column = 'title';

    protected bool $detailInModal = true;

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Image::make('Изображение', 'image')->disk('public')->dir('news_images')->nullable(),
            Text::make('Заголовок', 'title'),
            Date::make('Дата создания', 'created_at')->sortable()->format('Y-m-d'),
        ];
    }

    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function formFields(): iterable
    {
        return [
            Box::make([
                Text::make('Заголовок', 'title')->required(),
                Textarea::make('Содержание', 'short_description')->required(),
                Url::make('Ссылка на источник', 'link')->required(),

                Preview::make('Предпросмотр изображения', 'image')
                    ->image()
            ])
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Заголовок', 'title'),
            Textarea::make('Содержание', 'short_description'),
            Url::make('Ссылка на источник', 'link'),
            Image::make('Изображение', 'image'),
            Date::make('Дата создания', 'created_at'),
            Date::make('Последнее обновление', 'updated_at'),
        ];
    }

    /**
     * @param News $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    protected function rules(mixed $item): array
    {
        return [
            'title' => 'required|string|max:255',
            'short_description' => 'required|string',
            'link' => 'required|url|max:255',
            'image' => 'nullable|image|max:2048',
        ];
    }

    protected function afterCreated(mixed $item): mixed
    {
        $this->fetchAndSaveImage($item);

        $this->cleanupNews();

        return null;
    }

    protected function afterUpdated(mixed $item): mixed
    {
        $this->fetchAndSaveImage($item);

        return null;
    }
    private function fetchAndSaveImage(Model $item): void
    {
        $currentLink = $item->link;
        $originalLink = $item->getOriginal('link');

        $linkChangedInRequest = $currentLink !== $originalLink;

        $conditionMetToFetch = $linkChangedInRequest || ($item->link && empty($item->image));


        if ($conditionMetToFetch) {

            $service = new NewsImageService();
            $newImagePath = $service->fetchImage($currentLink);

            if ($newImagePath) {
                $oldImagePath = $item->getOriginal('image');

                if ($oldImagePath && $oldImagePath !== $newImagePath && Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                } else if ($oldImagePath && $oldImagePath === $newImagePath) {
                    return;
                }

                $item->image = $newImagePath;
                if ($item->isDirty('image')) {
                    $item->save();
                }
            }
        }
    }
    protected function beforeDeleting(mixed $item): mixed
    {
        try {
            Log::info('NewsResource: beforeDeleting hook triggered for item ID: ' . ($item->id ?? 'N/A'));

            if (!empty($item->image)) {
                $imagePath = str_starts_with($item->image, 'news_images/')
                    ? $item->image
                    : 'news_images/' . $item->image;

                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                    Log::info('NewsResource: Deleted image file: ' . $imagePath);
                } else {
                    Log::info('NewsResource: Image file not found for deletion: ' . $imagePath);
                }
            } else {
                Log::info('NewsResource: No image to delete');
            }
        } catch (\Throwable $e) {
            Log::error('NewsResource: Error in beforeDeleting: ' . $e->getMessage());
        }

        return null;
    }

    private function cleanupNews(): void
    {
        $newsCount = News::count();
        $threshold = 10;

        if ($newsCount > $threshold) {
            $itemsToDelete = $newsCount - $threshold;

            $oldestNews = News::orderBy('created_at', 'asc')
                ->take($itemsToDelete)
                ->get();

            $ids = $oldestNews->pluck('id')->implode(', ');
            Log::info('NewsResource: cleanupNews triggered. Deleting IDs: ' . $ids);

            $oldestNews->each(function (News $news) {
                if ($news) {
                    $news->delete();
                }
            });
        } else {
            Log::info('NewsResource: cleanupNews triggered. No items to delete.');
        }
    }
}
