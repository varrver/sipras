#!/usr/bin/env python3
"""Render the report figures in docs/diagrams/ to PNG.

Two server-side renderers are used, so no local Java/Graphviz/npm install
is needed:

- docs/diagrams/*.puml  -> PlantUML server (https://www.plantuml.com/plantuml/):
  the source is raw-deflate compressed and base64-encoded (PlantUML's custom
  alphabet) into the request URL. The SVG flavour is fetched first because the
  server returns HTTP 200 with an error *image* for broken sources.

- docs/erd.md (erDiagram block) -> mermaid.ink (https://mermaid.ink):
  the mermaid code is wrapped in a JSON state and base64-encoded into the URL.
  The ERD is the single source of truth for the schema, so the figure embedded
  in the report is rendered from exactly the block documented there.
  mermaid.ink answers HTTP 400 with the parser message on bad input.

Usage:  python3 scripts/render_diagrams.py
Writes: docs/diagrams/<name>.png (one per .puml, plus erd.png).

The report figures are named once in scripts/figures.py, which this script and
scripts/generate_laporan.py both import — the renderer's output names and the
report's embed paths cannot drift apart.
"""

import base64
import json
import re
import struct
import sys
import urllib.error
import urllib.request
import zlib
from pathlib import Path

from figures import DIAGRAMS, ERD, ERD_MD, REPORT_FIGURES, ROOT, png

PLANTUML_BASE = "https://www.plantuml.com/plantuml"
MERMAID_INK = "https://mermaid.ink/img"
ALPHABET = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-_"


def plantuml_encode(text: str) -> str:
    """Encode PlantUML source into the URL fragment the server expects."""
    compressor = zlib.compressobj(9, zlib.DEFLATED, -zlib.MAX_WBITS)
    raw = compressor.compress(text.encode("utf-8")) + compressor.flush()

    out = []
    for i in range(0, len(raw), 3):
        chunk = raw[i : i + 3]
        b0 = chunk[0]
        b1 = chunk[1] if len(chunk) > 1 else 0
        b2 = chunk[2] if len(chunk) > 2 else 0
        out.append(ALPHABET[b0 >> 2])
        out.append(ALPHABET[((b0 & 0x3) << 4) | (b1 >> 4)])
        out.append(ALPHABET[((b1 & 0xF) << 2) | (b2 >> 6)] if len(chunk) > 1 else "-")
        out.append(ALPHABET[b2 & 0x3F] if len(chunk) > 2 else "_")
    return "".join(out)


def fetch(url: str) -> bytes:
    request = urllib.request.Request(url, headers={"User-Agent": "sipras-docs/1.0"})
    with urllib.request.urlopen(request, timeout=60) as response:
        return response.read()


def png_size(data: bytes) -> tuple[int, int]:
    """Read width/height from the PNG IHDR chunk (bytes 16..24)."""
    return struct.unpack(">II", data[16:24])


def save_png(path: Path, data: bytes) -> None:
    path.write_bytes(data)
    width, height = png_size(data)
    print(f"{path.name}: {width}x{height}px, {len(data) / 1024:.0f} KiB")


def render_plantuml(name: str) -> None:
    source = (DIAGRAMS / f"{name}.puml").read_text(encoding="utf-8")
    encoded = plantuml_encode(source)

    svg = fetch(f"{PLANTUML_BASE}/svg/{encoded}").decode("utf-8", "replace")
    if "syntax error" in svg.lower():
        sys.exit(f"{name}.puml: PlantUML rejected the source:\n{svg[:2000]}")

    save_png(png(name), fetch(f"{PLANTUML_BASE}/png/{encoded}"))


def extract_er_diagram() -> str:
    md = ERD_MD.read_text(encoding="utf-8")
    blocks = re.findall(r"```mermaid\n(.*?)```", md, re.S)
    return next(b for b in blocks if b.strip().startswith("erDiagram"))


def render_erd() -> None:
    code = extract_er_diagram()
    state = json.dumps({"code": code, "mermaid": {"theme": "neutral"}}).encode("utf-8")
    token = base64.urlsafe_b64encode(state).decode("ascii")

    try:
        data = fetch(f"{MERMAID_INK}/{token}?type=png&width=2800")
    except urllib.error.HTTPError as err:
        detail = err.read().decode("utf-8", "replace")[:1000]
        sys.exit(f"docs/erd.md: mermaid.ink rejected the diagram (HTTP {err.code}):\n{detail}")

    save_png(png(ERD), data)


def check_report_figures() -> None:
    """Fail before rendering when the canonical set and the sources disagree."""
    for stem in REPORT_FIGURES:
        source = DIAGRAMS / f"{stem}.puml"
        if stem != ERD and not source.exists():
            sys.exit(f"{stem}: missing {source.relative_to(ROOT)}")

    if not ERD_MD.exists():
        sys.exit(f"missing {ERD_MD.relative_to(ROOT)}")


if __name__ == "__main__":
    check_report_figures()
    for puml in sorted(DIAGRAMS.glob("*.puml")):
        render_plantuml(puml.stem)
    render_erd()
