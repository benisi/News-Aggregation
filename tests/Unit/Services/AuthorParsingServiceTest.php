<?php

namespace Tests\Unit\Services;

use App\Services\AuthorParsingService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AuthorParsingServiceTest extends TestCase
{
    private AuthorParsingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuthorParsingService();
    }

    /**
     * Provides various test cases for the parsing logic.
     * @return array
     */
    public static function authorStringProvider(): array
    {
        return [
            'null input' => [null, []],
            'empty string' => ['', []],
            'single author' => ['John Doe', ['John Doe']],
            'single author with "By" prefix' => ['By Jane Smith', ['Jane Smith']],
            'two authors with "and"' => ['John Doe and Jane Smith', ['John Doe', 'Jane Smith']],
            'multiple authors with commas' => ['John Doe, Jane Smith, Jack Ryan', ['John Doe', 'Jane Smith', 'Jack Ryan']],
            'mixed delimiters' => ['By John Doe, Jane Smith and Jack Ryan', ['John Doe', 'Jane Smith', 'Jack Ryan']],
            'extra whitespace' => ['  John Doe ,  Jane Smith  ', ['John Doe', 'Jane Smith']],
            'inconsistent casing' => ['bY jOhN dOe aNd jAnE sMiTh', ['John Doe', 'Jane Smith']],
            'duplicate authors' => ['John Doe, Jane Smith and John Doe', ['John Doe', 'Jane Smith']],
            'empty parts from delimiters' => ['John Doe, , Jane Smith and ', ['John Doe', 'Jane Smith']],
        ];
    }

    #[Test]
    #[DataProvider('authorStringProvider')]
    public function it_correctly_parses_various_author_strings(?string $rawString, array $expected): void
    {
        $result = $this->service->parse($rawString);

        $this->assertEqualsCanonicalizing($expected, $result);
    }
}
