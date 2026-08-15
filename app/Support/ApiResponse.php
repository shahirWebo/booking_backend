<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use JsonSerializable;
use LogicException;

final class ApiResponse
{
    /**
     * Return a successful API response using the canonical top-level envelope.
     *
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    public static function success(
        mixed $data,
        int $status = 200,
        ?string $message = null,
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        $payload = [
            'success' => true,
            'data' => self::normalize($data),
            'meta' => self::meta($meta),
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return response()->json($payload, $status, $headers);
    }

    /**
     * @param  iterable<mixed>  $items
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    public static function collection(
        iterable $items,
        int $status = 200,
        ?string $message = null,
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        return self::success(
            array_map(self::normalize(...), is_array($items) ? $items : iterator_to_array($items)),
            $status,
            $message,
            $meta,
            $headers,
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    public static function created(
        mixed $data,
        ?string $location = null,
        ?string $message = null,
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        if ($location !== null) {
            $headers['Location'] = $location;
        }

        return self::success($data, 201, $message, $meta, $headers);
    }

    /**
     * @param  array<string, string>  $headers
     */
    public static function noContent(array $headers = []): Response
    {
        return response()->noContent(204, $headers);
    }

    /**
     * @param  callable(mixed): mixed|null  $transform
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     * @param  LengthAwarePaginator<int, mixed>|CursorPaginator<int, mixed>  $paginator
     */
    public static function paginated(
        LengthAwarePaginator|CursorPaginator $paginator,
        ?callable $transform = null,
        ?string $message = null,
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        $data = $paginator->getCollection()
            ->map(fn (mixed $item): mixed => self::normalize($transform ? $transform($item) : $item))
            ->values()
            ->all();

        $pagination = $paginator instanceof LengthAwarePaginator
            ? [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ]
            : [
                'per_page' => $paginator->perPage(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
                'has_more' => $paginator->hasMorePages(),
            ];

        $payload = [
            'success' => true,
            'data' => $data,
            'meta' => self::meta([
                ...$meta,
                'pagination' => $pagination,
            ]),
            'links' => $paginator instanceof LengthAwarePaginator
                ? [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ]
                : [
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return response()->json($payload, 200, $headers);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return object|array<string, mixed>
     */
    private static function meta(array $meta): object|array
    {
        return $meta === [] ? (object) [] : $meta;
    }

    private static function normalize(mixed $data): mixed
    {
        if ($data instanceof Model) {
            throw new LogicException('API responses must use an API Resource or explicit projection, not an Eloquent model.');
        }

        if ($data instanceof JsonResource) {
            return $data->resolve(request());
        }

        if ($data instanceof Arrayable) {
            return self::normalize($data->toArray());
        }

        if ($data instanceof JsonSerializable) {
            return self::normalize($data->jsonSerialize());
        }

        if (is_array($data)) {
            return array_map(self::normalize(...), $data);
        }

        return $data;
    }
}
