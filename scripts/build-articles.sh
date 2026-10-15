#!/opt/homebrew/bin/bash

# TODO: remove all .polished.md stuff and just convert all markdown files


SOURCE_DIR="markdown"
TARGET_DIR="html_polished"

# Ensure the target root exists
mkdir -p "$TARGET_DIR"

# Find all unique directories that contain at least one .md file
find "$SOURCE_DIR" -type f -name "*.md" -exec dirname {} \; | sort -u | while read -r current_dir; do

    # Count the total number of Markdown files in this specific folder
    # Using a local nullglob loop to safely count without parsing ls
    shopt -s nullglob
    md_files=("$current_dir"/*.md)
    md_count=${#md_files[@]}
    shopt -u nullglob

    # Skip directory if no markdown files are found (failsafe)
    [ $md_count -eq 0 ] && continue

    input_file=""

    if [ $md_count -eq 1 ]; then
        # Condition A: Only one file exists, so we use it directly
        input_file="${md_files[0]}"
    else
        # Condition B: Multiple files exist, prioritize the polished one
        for file in "${md_files[@]}"; do
            if [[ "$file" == *".polished."* ]]; then
                input_file="$file"
                break
            fi
        done
    fi

    # If multiple files existed but none were marked '.polished.', skip to avoid guessing
    if [ -z "$input_file" ]; then
        echo "Warning: Multiple Markdown files found in $current_dir, but none contain '.polished.'. Skipping."
        continue
    fi

    # 1. Swap the root directory name
    target_path_full="${input_file/$SOURCE_DIR/$TARGET_DIR}"

    # 2. Create the target subdirectory structure
    output_dir=$(dirname "$target_path_full")
    mkdir -p "$output_dir"

    # 3. Strip extensions safely to define the final HTML filename
    # This regex removes both .polished.md and standard .md extensions
    filename=$(basename "$target_path_full")
    clean_filename="${filename%.md}"
    clean_filename="${clean_filename%.polished}"
    output_file="$output_dir/${clean_filename}.html"

    echo "Converting: $input_file -> $output_file"

    # 4. Run Pandoc
    pandoc "$input_file" --mathjax -o "$output_file"

    # 5. Copy data.json from the source directory if it exists
    source_json="$current_dir/data.json"
    if [ -f "$source_json" ]; then
        cp "$source_json" "$output_dir/data.json"
        echo "Copied: $source_json -> $output_dir/data.json"
    fi
done

echo "HTML conversion complete."