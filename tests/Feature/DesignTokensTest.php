<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use SplFileInfo;
use Tests\TestCase;

class DesignTokensTest extends TestCase
{
    /**
     * Tailwind's colour utilities: a token is spent as `<utility>-<token>`, under
     * any variant prefix (`hover:bg-surface`, `file:bg-secondary-container`,
     * `placeholder:text-on-surface-variant`). Longest first so `ring-offset-…`
     * cannot be mistaken for `ring`. A new Tailwind colour utility has to be added
     * here, or its token will read as dead.
     */
    private const COLOUR_UTILITIES = [
        'ring-offset',
        'inset-shadow',
        'text-shadow',
        'decoration',
        'placeholder',
        'divide',
        'outline',
        'border',
        'shadow',
        'accent',
        'caret',
        'stroke',
        'fill',
        'text',
        'ring',
        'from',
        'via',
        'bg',
        'to',
    ];

    /**
     * Tokens deliberately consumed by nothing: the printed recap mirrors their
     * value by hand (DESIGN.md §5), so the token exists to name that value.
     * Add an entry only for that reason.
     */
    private const UNCONSUMED_BY_DESIGN = [
        '--color-surface-zebra',
    ];

    public function test_every_colour_token_is_consumed_by_something(): void
    {
        $defined = array_keys($this->colorTokens());
        $source = $this->consumableSource();

        foreach (self::UNCONSUMED_BY_DESIGN as $token) {
            $this->assertContains($token, $defined,
                "UNCONSUMED_BY_DESIGN lists {$token}, which app.css does not define — drop the entry.");
        }

        $unused = array_values(array_filter(
            $defined,
            fn (string $token) => ! in_array($token, self::UNCONSUMED_BY_DESIGN, true)
                && ! $this->isConsumed($source, substr($token, strlen('--color-')))
        ));

        $this->assertSame([], $unused,
            'These app.css @theme tokens are consumed by nothing — delete them, or list them in '
            .'UNCONSUMED_BY_DESIGN with a reason: '.implode(', ', $unused));
    }

    /**
     * Every file that can consume a token: the Blade views and the JS/PHP beside
     * them, plus app.css outside `@theme` (its own `@apply` and `var()` reads).
     * Tests and docs are excluded on purpose — a token named in an assertion is
     * not a use, and counting it would let this guard vouch for itself.
     */
    private function consumableSource(): string
    {
        return collect([resource_path(), app_path()])
            ->flatMap(fn (string $path) => File::allFiles($path))
            ->reject(fn (SplFileInfo $file) => str_ends_with($file->getFilename(), '.md'))
            ->map(fn (SplFileInfo $file) => file_get_contents($file->getPathname()))
            ->implode("\n");
    }

    /**
     * A token is consumed as a Tailwind utility (`bg-surface`, `divide-outline-variant/80`)
     * or read directly as a custom property (`var(--color-primary-container)`).
     *
     * The utility form is anchored on the utility name, not a bare substring, so a
     * component class like `.btn-secondary` cannot make `--color-secondary` look
     * alive.
     */
    private function isConsumed(string $source, string $token): bool
    {
        $utilities = implode('|', self::COLOUR_UTILITIES);
        $quoted = preg_quote($token, '/');

        return (bool) preg_match('/(?:^|[^a-z-])(?:'.$utilities.')-'.$quoted.'(?![a-z0-9-])/', $source)
            || (bool) preg_match('/var\(\s*--color-'.$quoted.'\s*\)/', $source);
    }
}
