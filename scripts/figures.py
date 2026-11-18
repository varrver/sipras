#!/usr/bin/env python3
"""Canonical names for the figures the report embeds.

`scripts/render_diagrams.py` writes these PNGs and `scripts/generate_laporan.py`
embeds them; both import this module, so the two can no longer disagree about
what a figure is called. The markdown links in docs/ are generated from the same
stems by `scripts/sync_figure_links.py`, so a rename here is the only edit.

Only these three are report figures (Gambar 2.1–2.3). Everything directly in
docs/diagrams/ is reproducible: these three plus the sub-diagrams rendered from
their own .puml (flowchart-1-…, flowchart-2-…, flowchart-3-…). The hand-drawn
copies live in docs/diagrams/hand-drawn/, which no script reads or writes.
"""

from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DOCS = ROOT / "docs"
DIAGRAMS = DOCS / "diagrams"

# Report order: Gambar 2.1 use case, 2.2 flowchart, 2.3 ERD.
REPORT_FIGURES = ("use-case", "flowchart", "erd")

# The ERD is the one figure with no .puml: it is rendered from the mermaid
# block in docs/erd.md, the source of truth for the schema.
ERD = "erd"
ERD_MD = DOCS / "erd.md"


def png(stem: str) -> Path:
    """Rendered PNG for a report figure (`'use-case'` -> docs/diagrams/use-case.png)."""
    return DIAGRAMS / f"{stem}.png"
