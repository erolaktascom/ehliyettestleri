<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class BlogPaginator extends LengthAwarePaginator
{
    private string $routeName;
    private array $routeParameters;

    public static function forListing(Builder $query, string $routeName, array $parameters, ?int $page): self
    {
        $posts = $query->paginate(9, page: $page);
        $paginator = new self($posts->getCollection(), $posts->total(), $posts->perPage(), $posts->currentPage());
        $paginator->routeName = $routeName;
        $paginator->routeParameters = $parameters;

        return $paginator;
    }

    public function url($page): string
    {
        $name = $this->routeName;
        $parameters = $this->routeParameters;
        if ($page > 1) {
            $parameters['page'] = $page;
            if ($name === 'blog.index') $name = 'blog.index.page';
        }

        return route($name, $parameters, false);
    }
}
