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
        $data = $request->all();

        $data['sourceIds'] = $request->input('source_id');
        $data['categoryIds'] = $request->input('category_id');
        $data['authorIds'] = $request->input('author_id');
        $data['dateFrom'] = $request->input('date_from');
        $data['dateTo'] = $request->input('date_to');
        $data['perPage'] = $request->input('per_page', self::DEFAULT_PER_PAGE);

        return self::fromArray($data);
    }

    public static function fromArray(array $data): self
    {
        $perPageFromInput = $data['perPage'] ?? self::DEFAULT_PER_PAGE;
        $perPage = self::DEFAULT_PER_PAGE;

        if ((int) $perPageFromInput > 0) {
            $perPage = max(1, min(100, (int) $perPageFromInput));
        }

        $sourceIds = self::safelyConvertCommaSeparatedToIds($data['source_id'] ?? $data['sourceIds'] ?? null);
        $categoryIds = self::safelyConvertCommaSeparatedToIds($data['category_id'] ?? $data['categoryIds'] ?? null);
        $authorIds = self::safelyConvertCommaSeparatedToIds($data['author_id'] ?? $data['authorIds'] ?? null);

        $dateFromInput = $data['date_from'] ?? $data['dateFrom'] ?? null;
        $dateToInput = $data['date_to'] ?? $data['dateTo'] ?? null;

        $dateFrom = self::safelyParseDate($dateFromInput);
        $dateTo = self::safelyParseDate($dateToInput, false);

        return new self(
            search: $data['search'] ?? null,
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
