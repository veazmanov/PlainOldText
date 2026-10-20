#!/usr/bin/env bash

set -euo pipefail

# ./scripts/new-article.sh
# Creates articles/<slug>/ with a main.md and data.json.
cd "$(dirname "$0")/../articles"

# Ask for the article name
read -rp "Name: " NAME

# Convert name to a slug
SLUG=$(echo "$NAME" \
    | tr '[:upper:]' '[:lower:]' \
    | sed -E 's/[^a-z0-9]+/-/g; s/^-+//; s/-+$//')

# Create directory
mkdir -p "$SLUG"

# Create files
touch "$SLUG/data.json"
touch "$SLUG/main.md"

# Add title to main.md
echo "# $NAME" > "$SLUG/main.md"

# Collect metadata
read -rp "Year: " YEAR
read -rp "Month: " MONTH
read -rp "Day: " DAY
read -rp "Newspaper: " NEWSPAPER
read -rp "Category (newspaper, letter, book-chapter) [newspaper]: " CATEGORY
CATEGORY=${CATEGORY:-newspaper}
read -rp "Source URL: " SOURCE_URL

# Write JSON
cat > "$SLUG/data.json" <<EOF
{
  "year": $YEAR,
  "month": $MONTH,
  "day": $DAY,
  "title": "$NAME",
  "newspaper": "$NEWSPAPER",
  "category": "$CATEGORY",
  "source_url": "$SOURCE_URL"
}
EOF

# Give it a permanent id for its stable URL (/stable/<id>)
php ../scripts/assign-ids.php "$SLUG"

echo "Created:"
echo "  $SLUG/"
echo "    ├── main.md"
echo "    └── data.json"