<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Source;
use App\Models\SourceAlias;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SourceSeeder extends Seeder
{
    public function run(): void
    {
        $json = File::get(database_path('data/sources.json'));
        $data = json_decode($json);

        $groupedSources = [];
        foreach ($data->sources as $source) {
            $groupedSources[$source->category][] = $source;
        }

        foreach ($groupedSources as $categoryName => $sources) {
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => Str::title($categoryName)]
            );

            foreach ($sources as $sourceData) {
                $source = Source::updateOrCreate(
                    ['slug' => $sourceData->id],
                    [
                        'name'        => $sourceData->name,
                        'description' => $sourceData->description,
                        'url'         => $sourceData->url,
                        'category_id' => $category->id,
                    ]
                );

                SourceAlias::firstOrCreate(
                    ['name' => $sourceData->name],
                    ['source_id' => $source->id]
                );

                if (isset($sourceData->aliases) && is_array($sourceData->aliases)) {
                    foreach ($sourceData->aliases as $aliasName) {
                        SourceAlias::firstOrCreate(
                            ['name' => $aliasName],
                            ['source_id' => $source->id]
                        );
                    }
                }
            }
        }
    }
}
