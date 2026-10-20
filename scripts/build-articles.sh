#!/opt/homebrew/bin/bash

# ./scripts/build-articles.sh
# Converts every articles/<slug>/*.md to HTML with pandoc and copies the markdown and data.json alongside,
# into oldnews-articles-only/ (gitignored), which html/data.php reads and ./deploy uploads.
# A folder is one source; several .md files in it are editions of it (e.g. reading.md and diplomatic.md).

cd "$(dirname "$0")/.." || exit 1

SOURCE_DIR="articles"
TARGET_DIR="oldnews-articles-only"

# Start from an empty folder, so an article's old or renamed files (such as an edition .html
# left over from a previous file name) don't linger and show up as extra editions.
rm -rf "${TARGET_DIR:?}"
mkdir -p "$TARGET_DIR"

find "$SOURCE_DIR" -type f -name "*.md" | sort | while read -r input_file; do
    current_dir=$(dirname "$input_file")
    output_dir="${current_dir/#$SOURCE_DIR/$TARGET_DIR}"
    output_file="$output_dir/$(basename "$input_file" .md).html"
    mkdir -p "$output_dir"

    echo "Converting: $input_file -> $output_file"
    pandoc "$input_file" --math-method=mathjax -o "$output_file"

    # Copy the markdown too, served at /<slug>/markdown
    cp "$input_file" "$output_dir/"

    if [ -f "$current_dir/data.json" ]; then
        cp "$current_dir/data.json" "$output_dir/data.json"
    fi
done

echo "HTML conversion complete."
