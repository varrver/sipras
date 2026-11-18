<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use LogicException;

class GuestLayout extends Component
{
    /**
     * Create a new component instance.
     *
     * Pages pass the title *without* the app-name suffix; the layout appends it.
     * WCAG 2.4.2 wants a title that describes the page, and this shell serves the
     * auth pages and the four error pages, so a hardcoded app name is not enough.
     */
    public function __construct(public ?string $title = null)
    {
        // No fallback title. A page that omits `:title` used to render a tab that
        // named the product instead of the page (WCAG 2.4.2 — and invisible to
        // axe, since `document-title` passes on any non-empty string). Refusing
        // here is safe: the only way to arrive is a page someone just added, and
        // every existing page is pinned by tests/Feature/PageTitleTest.php.
        if (trim((string) $title) === '') {
            $route = request()->route()?->getName() ?? 'unnamed route';

            throw new LogicException(
                "The route [{$route}] rendered x-guest-layout without a title. Add ".
                'a `:title` attribute to the component tag — see DESIGN.md §2.6.'
            );
        }
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
