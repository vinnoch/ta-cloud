<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;

it('shows page one when a paginator has only one page', function () {
    $paginator = new LengthAwarePaginator(['row'], 1, 50, 1, ['path' => '/records']);

    $html = $paginator->links()->toHtml();

    expect($html)
        ->toContain('Showing 1 to 1 of 1 results')
        ->toContain('aria-current="page">1</span>');
});

it('hides pagination for an empty paginator', function () {
    $paginator = new LengthAwarePaginator([], 0, 50, 1, ['path' => '/records']);

    $html = $paginator->links()->toHtml();

    expect(trim($html))->toBe('');
});

it('renders real pagination above and below a shared paginated data table', function () {
    $paginator = new LengthAwarePaginator(['row'], 13, 12, 1, ['path' => '/library']);

    $html = Blade::render("@include('partials.tables.data-table', [
        'columns' => ['Name'],
        'rows' => [['Record']],
        'paginator' => \$paginator,
    ])", compact('paginator'));

    expect($html)
        ->toContain('Showing 1 to 1 of 13 results')
        ->toContain('href="/library?page=2"')
        ->and(substr_count($html, 'class="tacloud-pagination"'))->toBe(2);
});

it('renders page one above and below a shared non-paginated data table', function () {
    $html = Blade::render("@include('partials.tables.data-table', [
        'columns' => ['Name'],
        'rows' => [['Record']],
    ])");

    expect(substr_count($html, 'aria-current="page">1</span>'))->toBe(2);
});

it('hides pagination above and below an empty shared data table', function () {
    $html = Blade::render("@include('partials.tables.data-table', [
        'columns' => ['Name'],
        'rows' => [],
    ])");

    expect($html)
        ->not->toContain('class="tacloud-pagination"')
        ->not->toContain('aria-current="page"');
});
