<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsApiResource;
use App\Models\News;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="Landing Page News API Documentation",
 * description="API endpoints for the News section of the landing page",
 * @OA\Contact(
 * email="your-email@example.com"
 * )
 * )
 *
 * @OA\Server(
 * url=L5_SWAGGER_CONST_HOST,
 * description="Landing Page API Server"
 * )
 *
 * @OA\Tag(
 * name="News",
 * description="API Endpoints of News"
 * )
 */
class NewsApiController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/news/{page}/{limit}",
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
     * description="Неверные параметры запроса (например, limit не число или вне диапазона)",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Invalid pagination parameters"),
     * @OA\Property(property="errors", type="object", description="Details of validation errors")
     * )
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Внутренняя ошибка сервера",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="An error occurred while fetching news.")
     * )
     * )
     * )
     */
    public function index($page, $limit)
    {
        $validator = Validator::make([
            'page' => $page,
            'limit' => $limit,
        ], [
            'page' => 'required|integer|min:1',
            'limit' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid pagination parameters',
                'errors' => $validator->errors(),
            ], 400);
        }

        $page = (int)$page;
        $limit = (int)$limit;

        try {
            $news = News::orderBy('created_at', 'desc')
                ->paginate($limit, ['*'], 'page', $page);

            return NewsApiResource::collection($news);
        } catch (Exception $e) {

            Log::error('Error fetching news: ' . $e->getMessage());

            return response()->json([
                'message' => 'An error occurred while fetching news.',
            ], 500);
        }
    }
}
