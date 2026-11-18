#!/usr/bin/env python3
"""Point the diagram links in docs/ at the figures scripts/figures.py names.

A line `<!-- figure: <stem> -->` marks the line below it as that figure's link;
the target of the first markdown link on that line is rewritten from the
registry. The link text and caption stay in the markdown — only the path is
generated — and a rename in figures.py is followed by rerunning this.

Usage:
    python3 scripts/sync_figure_links.py           # rewrite docs in place
    python3 scripts/sync_figure_links.py --check   # exit 1 if a link is stale
"""

from __future__ import annotations

import os
import re
import sys
from pathlib import Path

from figures import DOCS, REPORT_FIGURES, png

MARKER = re.compile(r"^<!--\s*figure:\s*(?P<stem>[a-z0-9-]+)\s*-->$")
LINK = re.compile(r"\]\((?P<target>[^)\s]+)\)")


def target_for(stem: str, md: Path) -> str:
    """Link target for `stem`, relative to the markdown file that carries it."""
    return os.path.relpath(png(stem), md.parent).replace(os.sep, "/")


def stale_links(md: Path) -> list[tuple[int, str, str]]:
    """`(0-based line index, current target, wanted target)` for marked links."""
    lines = md.read_text(encoding="utf-8").splitlines(keepends=True)
    stale = []

    for i, line in enumerate(lines):
        marker = MARKER.match(line.strip())
        if marker is None:
            continue

        stem = marker.group("stem")
        if stem not in REPORT_FIGURES:
            sys.exit(f"{md}:{i + 1}: '{stem}' is not a figure in scripts/figures.py")

        if i + 1 == len(lines):
            sys.exit(f"{md}:{i + 1}: figure marker with no line below it")

        link = LINK.search(lines[i + 1])
        if link is None:
            sys.exit(f"{md}:{i + 2}: expected a markdown link below the figure marker")

        wanted = target_for(stem, md)
        if link.group("target") != wanted:
            stale.append((i + 1, link.group("target"), wanted))

    return stale


def rewrite(md: Path) -> list[str]:
    """Point every marked link in `md` at the registry; report what changed."""
    stale = stale_links(md)
    if not stale:
        return []

    lines = md.read_text(encoding="utf-8").splitlines(keepends=True)
    for index, current, wanted in reversed(stale):
        lines[index] = lines[index].replace(f"]({current})", f"]({wanted})", 1)

    md.write_text("".join(lines), encoding="utf-8")
    return [f"{md}:{index + 1}: {current} -> {wanted}" for index, current, wanted in stale]


def main(argv: list[str]) -> int:
    docs = sorted(DOCS.rglob("*.md"))

    if "--check" in argv:
        stale = [(md, entry) for md in docs for entry in stale_links(md)]
        for md, (index, current, wanted) in stale:
            print(f"{md}:{index + 1}: {current} should be {wanted}")
        return 1 if stale else 0

    for md in docs:
        for change in rewrite(md):
            print(change)

    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
