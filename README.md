# Old News Dot Com

This repo hosts sources.vjbe.net (Plain Old Text): the PHP site and the article transcriptions it serves.

- `articles/<slug>/` — one folder per source: `main.md` (the transcription) and `data.json` (title, newspaper, category, date, summary, scans; category is `newspaper`, `letter` or `book-chapter`). See `articles/data.example.json`. Articles are public domain (`articles/UNLICENSE`).
  - A source with several transcriptions has one `.md` per edition instead of `main.md`, e.g. `reading.md` and `diplomatic.md` (the reading edition is the default; the others are at `?edition=<name>`).
  - Every article has a permanent `id` (5 random characters), and its canonical URL is `/stable/<id>` (with `/scans` and `/markdown` under it); the old `/<slug>` URLs redirect there. IDs are permanent: never edit an `id`, and never reuse one, even for a deleted article. `php scripts/assign-ids.php` gives any article without one a new `id` (`new-article.sh` runs it for you).
  - Books use `publication` instead of `newspaper`, and may give just a `year`.
  - Scans are page images on oldnews-photos.vjbe.net (`scans`, written by `sync_scans.php`), or one PDF anywhere (`scans_pdf`, a URL).
  - Each article shows a Chicago footnote citation ("Cite as", see `html/citation.php`), from: `author`, `place` (city, for newspapers whose title doesn't name it), and for books `full_title`, `translator`, `printer`; for journals (category `journal`) `volume` and `issue`.
- `html/` — the PHP site.
- `scripts/` — `new-article.sh` creates an article folder, `assign-ids.php` gives new articles their permanent `id`, `build-articles.sh` converts the markdown to HTML with pandoc, `sync_scans.php` records scan filenames from oldnews-photos.vjbe.net into `data.json`.

## Workflow

- `./scripts/build-articles.sh` builds into `oldnews-articles-only/` (gitignored), which the site reads.
- `./dev` runs the site at localhost:8000 (build the articles first).
- After adding an article by hand, run `php scripts/assign-ids.php` to give it an `id`.
- After uploading scans, run `php scripts/sync_scans.php [slug]`.
- `./deploy` builds the articles and uploads both the site and the articles.
