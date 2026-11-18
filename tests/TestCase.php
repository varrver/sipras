<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\View\ViewException;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Assert that requesting $url fails loudly instead of rendering, and that the
     * failure names its cause.
     *
     * Blade wraps whatever a view threw — `ViewException`, sometimes nested — so
     * the guard is asserted as the *root* cause, not the exception that surfaces.
     * Call `withoutExceptionHandling()` first: otherwise the kernel turns it into a
     * 500 response rather than letting it out.
     */
    protected function assertFailsLoudly(string $url, string ...$expectedMessage): void
    {
        try {
            $this->get($url);
        } catch (ViewException $e) {
            $cause = $e;

            while ($cause->getPrevious() !== null) {
                $cause = $cause->getPrevious();
            }

            $this->assertInstanceOf(LogicException::class, $cause, "{$url} did not fail on its guard.");

            foreach ($expectedMessage as $fragment) {
                $this->assertStringContainsString($fragment, $e->getMessage(), "{$url} failed without saying why.");
            }

            return;
        }

        $this->fail("{$url} rendered instead of failing loudly.");
    }

    /**
     * The `--color-*` values declared in app.css' `@theme` block.
     *
     * @return array<string, string> map of `--color-name` => lowercased hex
     */
    protected function colorTokens(): array
    {
        preg_match_all(
            '/--color-([a-z-]+):\s*(#[0-9a-fA-F]{3,8})\s*;/',
            file_get_contents(resource_path('css/app.css')),
            $matches,
            PREG_SET_ORDER
        );

        return collect($matches)
            ->mapWithKeys(fn (array $match) => ["--color-{$match[1]}" => strtolower($match[2])])
            ->all();
    }
}
