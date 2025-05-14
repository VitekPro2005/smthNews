<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsApiResource;
use App\Models\News;
class NewsApiController extends Controller
{
    /**
     * @OA\Get(
     * path="/news/{page}/{limit}",
     * operationId="getNewsList",
     * tags={"News"},
     * summary="Получить список новостей с пагинацией",
     * description="Возвращает список новостей по указанной странице и лимиту. Сортировка по дате создания (новые сверху).",
     * @OA\Parameter(
     * name="page",
     * description="Номер страницы (начиная с 1)",
     * required=true,
     * in="path",
     * @OA\Schema(
     * type="integer",
     * default=1,
     * minimum=1
     * )
     * ),
     * @OA\Parameter(
     * name="limit",
     * description="Количество новостей на странице (для лендинга обычно 4). Максимум 100.",
     * required=true,
     * in="path",
     * @OA\Schema(
     * type="integer",
     * default=4,
     * minimum=1,
     * maximum=100
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Успешный ответ со списком новостей и метаданными пагинации",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(
     * property="data",
     * type="array",
     * @OA\Items(ref="#/components/schemas/News")
     * ),
     * @OA\Property(
     * property="links",
     * type="object",
     * description="Pagination links"
     * ),
     * @OA\Property(
     * property="meta",
     * type="object",
     * description="Pagination metadata"
     * )
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Неверные параметры запроса"
     * ),
     * @OA\Response(
     * response=500,
     * description="Внутренняя ошибка сервера"
     * )
     * )
     */

    public function index(int $page, int $limit)
    {
        $news = News::orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return NewsApiResource::collection($news);
    }
}
