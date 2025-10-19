<?php

namespace App\DTOs;

use Carbon\Carbon;
use Illuminate\Http\Request;

readonly class ArticleFilterDTO
{
    const DEFAULT_PER_PAGE = 15;

    public function __construct(
        public readonly ?string $search,
        public readonly array $sourceIds,
        public readonly array $categoryIds,
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo,
        public readonly int $perPage,
        public readonly array $authorIds
    ) {}

    public static function fromRequest(Request $request): self
    {
        $perPageFromRequest = $request->input('per_page', self::DEFAULT_PER_PAGE);
        $perPage = self::DEFAULT_PER_PAGE;

        if ((int) $perPageFromRequest > 0) {
            $perPage = max(1, min(100, (int) $perPageFromRequest));
        }

        $dateFrom = self::safelyParseDate($request->input('date_from'));
        $dateTo = self::safelyParseDate($request->input('date_to'), false);

        $sourceIds = self::safelyConvertCommaSeparatedToIds($request->input('source_id'));
        $categoryIds = self::safelyConvertCommaSeparatedToIds($request->input('category_id'));
        $authorIds = self::safelyConvertCommaSeparatedToIds($request->input('author_id'));

        return new self(
            search: $request->input('search'),
            sourceIds: $sourceIds,
            categoryIds: $categoryIds,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            perPage: $perPage,
            authorIds: $authorIds
        );
    }

    protected static function safelyConvertCommaSeparatedToIds(?string $input): array
    {
        if (empty($input)) {
            return [];
        }

        return collect(explode(',', $input))
            ->map(fn($id) => (int) trim($id))
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected static function safelyParseDate(?string $dateString, bool $start = true): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            $date = Carbon::parse($dateString);

            if($start) {
                $date->startOfDay();
            } else {
                $date->endOfDay();
            }

            return $date->toDateTimeString();
        } catch (\Exception) {
            return null;
        }
    }
}
