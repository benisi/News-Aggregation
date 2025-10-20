<?php

namespace App\Jobs;

use App\Exceptions\MaximumArticleResultException;
use App\Exceptions\SourceNotFoundException;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\SourceAlias;
use App\Services\DataSources\DataFetcherInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AggregateArticle implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    /**
     * Create a new job instance.
     */
    public function __construct(protected DataFetcherInterface $fetcher, protected int $page = 1) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $articles = $this->fetcher->fetch($this->page);
        } catch (MaximumArticleResultException) {
            return;
        }

        if ($articles->isEmpty()) {
            return;
        }

        foreach ($articles as $article) {
            $sourceAlias = SourceAlias::whereSlug(Str::slug($article->source))->first();

            if (!$sourceAlias) {
                report(new SourceNotFoundException("Source '{$article->source}' not found, add to the whilelisted sources"));
                continue;
            }

            DB::transaction(function () use ($article, $sourceAlias) {
                $categoryName = $article->category ?? $sourceAlias->source->category->name;
                $category = Category::firstOrCreate(
                    ['slug' => Str::slug($categoryName)],
                    ['name' => Str::title($categoryName)]
                );

                $savedArticle = Article::updateOrCreate(
                    ['url' => $article->url],
                    array_merge($article->toArray(), [
                        'category_id' => $category->id,
                        'source_id' => $sourceAlias->source_id
                    ]),
                );

                $authorIds = collect($article->authors)->map(function ($authorName) use ($sourceAlias) {
                    return Author::firstOrCreate([
                        'name' => $authorName,
                        'source_id' => $sourceAlias->source_id
                    ])->id;
                })->all();

                $savedArticle->authors()->sync($authorIds);
            });
        }

        if (!$articles->getIsLastPage()) {
            dispatch(new AggregateArticle($this->fetcher, $this->page + 1));
        }
    }
}
