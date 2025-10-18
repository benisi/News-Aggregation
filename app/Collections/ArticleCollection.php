<?php

namespace App\Collections;

use App\DTOs\ArticleDTO;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @extends \Illuminate\Support\Collection<int, \App\DTOs\ArticleDTO>
 */
class ArticleCollection extends Collection
{
    protected bool $isLastPage = false;

    public function __construct($items = [])
    {
        foreach ($items as $item) {
            if (! $item instanceof ArticleDTO) {
                throw new InvalidArgumentException(
                    'All items in ' . static::class . ' must be instances of ' . ArticleDTO::class
                );
            }
        }

        parent::__construct($items);
    }

    public function add($item)
    {
        if (! $item instanceof ArticleDTO) {
            throw new InvalidArgumentException(
                'Cannot add non-ArticleDTO instance to ' . static::class
            );
        }

        return parent::add($item);
    }

    public function merge($items)
    {
        if ($items instanceof self === false) {
            throw new InvalidArgumentException('Can only merge another ' . static::class);
        }

        return parent::merge($items);
    }

    public function setIsLastPage(bool $isLastPage): void
    {
        $this->isLastPage = $isLastPage;
    }

    public function getIsLastPage(): bool
    {
        return $this->isLastPage;
    }
}
