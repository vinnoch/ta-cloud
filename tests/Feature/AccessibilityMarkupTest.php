<?php

use Illuminate\Support\Facades\File;

it('gives modal containers accessible dialog semantics', function () {
    $violations = [];

    foreach (File::allFiles(resource_path('views')) as $file) {
        preg_match_all('/<div\b[^>]*class="[^"]*\bacss-modal\b[^"]*"[^>]*>/i', $file->getContents(), $matches);

        foreach ($matches[0] as $tag) {
            if (! str_contains($tag, 'role="dialog"') || ! str_contains($tag, 'aria-modal="true"')) {
                $violations[] = $file->getRelativePathname();
            }
        }
    }

    expect(array_values(array_unique($violations)))->toBe([]);
});
