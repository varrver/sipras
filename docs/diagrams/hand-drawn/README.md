# Hand-drawn diagram exports

Second versions of the three report figures (use case, flowchart, ERD), drawn by
hand instead of rendered from the PlantUML sources. Kept for reference.

Nothing generates these files and nothing in the repo embeds them. Each one is
a hand-drawn counterpart of a generated figure in the parent folder; those are
the versions the report and the `docs/` links use, and their filenames live in
`scripts/figures.py`.

`python3 scripts/render_diagrams.py` rewrites the generated PNGs and never
touches this folder, so everything directly in `docs/diagrams/` is reproducible
output. Don't add rendered figures here.
