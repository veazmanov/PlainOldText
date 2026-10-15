# Old News Dot Com

Main is broken, work on 'fixed-version'

This repo hosts oldnews.vjbe.net: the PHP site and the article transcriptions it serves.

- `articles/<slug>/` — one folder per article: `main.md` (the transcription) and `data.json` (title, newspaper, date, summary, scans). See `articles/data.example.json`. Articles are public domain (`articles/UNLICENSE`).
- `html/` — the PHP site.
- `scripts/` — `new-article.sh` creates an article folder, `build-articles.sh` converts the markdown to HTML with pandoc, `sync_scans.php` records scan filenames from oldnews-photos.vjbe.net into `data.json`.

## Workflow

- `./scripts/build-articles.sh` builds into `oldnews-articles-only/` (gitignored), which the site reads.
- `./dev` runs the site at localhost:8000 (build the articles first).
- After uploading scans, run `php scripts/sync_scans.php [slug]`.
- `./deploy` builds the articles and uploads both the site and the articles.
