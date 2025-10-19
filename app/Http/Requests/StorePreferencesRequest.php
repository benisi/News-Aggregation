<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preferred_sources' => 'required_without_all:preferred_categories,preferred_authors|array',
            'preferred_sources.*' => [
                'integer',
                Rule::exists('sources', 'id'),
            ],
            'preferred_categories' => 'required_without_all:preferred_sources,preferred_authors|array',
            'preferred_categories.*' => ['integer', Rule::exists('categories', 'id')],
            'preferred_authors' => 'required_without_all:preferred_sources,preferred_categories|array',
            'preferred_authors.*' => ['integer', Rule::exists('authors', 'id')],
        ];
    }
}
