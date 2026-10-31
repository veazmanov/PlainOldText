# Plain Old Text

A free archive of historical sources transcribed as plain text, each with scans of the original pages, a link to the digital source and a Chicago-style citation. Live at <https://sources.vjbe.net>.

This repo holds both the transcriptions (`articles/`) and the PHP site that serves them (`html/`). The code and the transcriptions are public domain (`LICENSE`, `articles/UNLICENSE`).

## Requirements

- PHP 8 (the GD extension is optional; without it, catalogue cards link to the full scan instead of a cropped one)
- [pandoc](https://pandoc.org), to build the articles
- bash at `/opt/homebrew/bin/bash` for `deploy` and `build-articles.sh`

## Layout

- `articles/<slug>/` — one folder per source, with the transcription as `main.md` and its metadata in `data.json`.
- `html/` — the site. `index.php` is the front controller; `data.php` loads the articles and holds the site settings; `citation.php` builds citations; `search.php` is the full-text search at `/search`; `card_scan.php` makes the catalogue-card images.
- `scripts/` — `new-article.sh`, `assign-ids.php`, `build-articles.sh`, `sync_scans.php` and `tcp-to-articles.php`, with its configs and TCP texts in `scripts/tcp/` (see Workflow).

## Articles

A source with several transcriptions has one `.md` per edition instead of `main.md`, e.g. `reading.md` and `diplomatic.md`. The reading edition is the default; others are at `?edition=<name>`.

A source in another language can have an English translation as `translation.md` beside its transcription. It is not an edition: the article page shows the translation instead of the text, under the text's editorial note, and "Original (Latin) text" puts the text beside it (`?original=1`), paragraph by paragraph, so keep the transcription's paragraphing; the language comes from `language` in `data.json`. There the translation's title is dropped and its editorial note shown under the text's. Editor's notes on the translation have `"edition": "translation"`.

`data.json` (see `articles/data.example.json`) has:

- `id` — a permanent 5-character id. The article's canonical URL is `/stable/<id>`, with `/stable/<id>/scans` (scans open) and `/stable/<id>/markdown` (the plain-text source). Old `/<slug>` and `/photocopy/<slug>` URLs redirect there. Never edit an `id`, and never reuse one, even for a deleted article.
- `title`, `summary`, `source_url` (the digitised original), `page`.
- `category` — `newspaper`, `letter`, `book-chapter` or `journal`.
- `newspaper`, or `publication` for books and other sources.
- `year`, `month`, `day` — a full date, or just `year` (e.g. for a book).
- `scans` — page images on oldnews-photos.vjbe.net, written by `sync_scans.php`; or `scans_pdf`, the URL of a single PDF.
- Optional: `featured` (highlighted in the catalogue) and `card_position` (the CSS `background-position` of the first scan on its catalogue card).
- Citation fields: `author`, `place` (the city, for newspapers whose title doesn't name it), `full_title`, `translator` and `printer` for books, `volume` and `issue` for journals.
- Optional: `language` — the language of the text, e.g. `Latin`, for an article with an English translation.
- Optional: `collection` — the key of the collection the article is a part of (see Collections).
- Optional: `notes` — editor's notes, shown as lettered sidenotes (a, b, c…) beside the text. Each has `edition` (the `.md` file name without extension), `type` (`explanatory`, `sic` for a misprint kept in the text, or `query` for a likely transcription error), `on` (the exact phrase the note follows, which must occur once in the text as a reader sees it) and `note` (Markdown *italics* and `[links](https://…)` allowed). `php scripts/check-notes.php` checks every anchor.

## Collections

A work in several parts, such as the chapters of a book, is a collection: each part is an ordinary article whose `data.json` has `"collection": "<key>"`, and `articles/collections.json` gives each key an `id`, a `title` and a `summary`. The catalogue shows a collection as one card, linking to its contents page at `/stable/<id>`; each part links to the contents and to the parts before and after it. Parts are in folder order, so number their folders (e.g. `hume-1779-dialogues-01-part-i`). The byline and date come from the first part. `php scripts/assign-ids.php` (with no arguments) gives new collections their ids, from the same pool as articles'.

## Workflow

- `./scripts/new-article.sh` asks for the title, date and source, and creates `articles/<slug>/` with an `id`. After adding an article by hand, run `php scripts/assign-ids.php` to give it one; the site skips articles without an `id`.
- After uploading scans to oldnews-photos.vjbe.net, run `php scripts/sync_scans.php [slug]` to record them in `data.json`.
- `php scripts/tcp-to-articles.php scripts/tcp/<id>.json scripts/tcp/<id>.xml` turns an EEBO-TCP text (XML from [github.com/textcreationpartnership](https://github.com/textcreationpartnership)) into articles, one per division named in the config, each with a `diplomatic.md` whose page markers link to a scan on the Internet Archive. It writes `data.json` only for a new article, so rerunning it keeps your edits there; run `assign-ids.php` after. It reports folio numbers out of sequence and pages TCP's copy lacks.
- `./scripts/build-articles.sh` converts the markdown to HTML with pandoc into `oldnews-articles-only/` (gitignored), which the site reads. Rebuild after changing an article.
- To show an article you have just edited as "Last edited" above the catalogue, set `$last_edited` in `html/data.php` to its `id` (or `null` for none).
- `./dev` runs the site at <http://localhost:8001>.
- `./deploy` builds the articles and uploads the site and the built articles. It relies on a separate `deploy` command on your `PATH`; scans are uploaded separately.
