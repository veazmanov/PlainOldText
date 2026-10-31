#!/opt/homebrew/bin/bash

# ./scripts/build-articles.sh
# Converts each articles/<slug>/*.md to HTML with pandoc and copies the markdown and data.json alongside,
# into oldnews-articles-only/ (gitignored), which html/data.php reads and ./deploy uploads.
# A folder is one source; several .md files in it are editions of it (e.g. reading.md and diplomatic.md).
# collections.json is copied too. Each commentary/<name>.md becomes _commentary/<name>.html, with its front
# matter as <name>.json.

cd "$(dirname "$0")/.." || exit 1

SOURCE_DIR="articles"
TARGET_DIR="oldnews-articles-only"

# Only what changed is rebuilt: pandoc runs when a .md is newer than its .html, and other files are copied
# only when they differ, so unchanged files keep their modification times (which rsync, in ./deploy, and the
# search cache go by). Built files whose source is gone are removed, so an article's old or renamed files
# (such as an edition .html left over from a previous file name) don't linger and show up as extra editions.
# --force rebuilds everything, e.g. after upgrading pandoc.
if [ "${1:-}" = "--force" ]; then
    rm -rf "${TARGET_DIR:?}"
fi
mkdir -p "$TARGET_DIR/_commentary"

# Every file the build should produce, to find the stale ones afterwards
declare -A wanted

# Copy a file unless the copy is already identical
copy_if_changed() {
    cmp -s "$1" "$2" || cp "$1" "$2"
    wanted["$2"]=1
}

# Run pandoc ($1 the source, $2 the output, the rest its options) if the output is missing or older
build_if_changed() {
    local input_file=$1 output_file=$2
    shift 2
    if [ ! -f "$output_file" ] || [ "$input_file" -nt "$output_file" ]; then
        echo "Converting: $input_file -> $output_file"
        pandoc "$input_file" "$@" -o "$output_file"
    fi
    wanted["$output_file"]=1
}

while read -r input_file; do
    current_dir=$(dirname "$input_file")
    output_dir="${current_dir/#$SOURCE_DIR/$TARGET_DIR}"
    mkdir -p "$output_dir"

    build_if_changed "$input_file" "$output_dir/$(basename "$input_file" .md).html" --math-method=mathjax

    # Copy the markdown too, served at /stable/<id>/markdown
    copy_if_changed "$input_file" "$output_dir/$(basename "$input_file")"

    if [ -f "$current_dir/data.json" ]; then
        copy_if_changed "$current_dir/data.json" "$output_dir/data.json"
    fi
done < <(find "$SOURCE_DIR" -type f -name "*.md" | sort)

# The collections (works in several parts) the articles belong to
copy_if_changed "$SOURCE_DIR/collections.json" "$TARGET_DIR/collections.json"

# Commentary on the articles, kept apart from them (see html/commentary.php)
for input_file in commentary/*.md; do
    [ -f "$input_file" ] || continue
    name=$(basename "$input_file" .md)
    build_if_changed "$input_file" "$TARGET_DIR/_commentary/$name.html" --math-method=mathjax
    build_if_changed "$input_file" "$TARGET_DIR/_commentary/$name.json" -t html --template=<(printf '%s\n' '$meta-json$')
    copy_if_changed "$input_file" "$TARGET_DIR/_commentary/$name.md"
done

# Remove what no longer has a source, then any folders left empty
while read -r built; do
    if [ -z "${wanted["$built"]:-}" ]; then
        echo "Removing: $built"
        rm "$built"
    fi
done < <(find "$TARGET_DIR" -type f)
find "$TARGET_DIR" -mindepth 1 -type d -empty -delete

echo "HTML conversion complete."
