#!/bin/sh

set -eu

export MSYS_NO_PATHCONV=1
export MSYS2_ARG_CONV_EXCL='*'

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
SOURCE="$ROOT_DIR/docs/instrukcja-klienta.md"
OUTPUT="${1:-$ROOT_DIR/docs/instrukcja-klienta.pdf}"
LOGO="${KRAMO_LOGO:-$ROOT_DIR/docs/logo.png}"

if ! command -v pandoc >/dev/null 2>&1; then
    cat >&2 <<'MSG'
pandoc is not installed, so the PDF cannot be built.

  Windows : winget install --id JohnMacFarlane.Pandoc
  macOS   : brew install pandoc
  Debian  : sudo apt install pandoc

A PDF engine is also required. Recommended: install TinyTeX
(https://yihui.org/tinytex/) or use --pdf-engine=weasyprint.
MSG
    exit 1
fi

if [ ! -f "$SOURCE" ]; then
    echo "Missing $SOURCE" >&2
    exit 1
fi

ENGINE=""
for candidate in xelatex lualatex pdflatex weasyprint wkhtmltopdf; do
    if command -v "$candidate" >/dev/null 2>&1; then
        ENGINE="$candidate"
        break
    fi
done

if [ -z "$ENGINE" ]; then
    echo "No PDF engine found (looked for xelatex, lualatex, pdflatex, weasyprint, wkhtmltopdf)." >&2
    echo "Install TinyTeX or weasyprint, then run this script again." >&2
    exit 1
fi

set -- "$SOURCE" --output="$OUTPUT" --pdf-engine="$ENGINE" \
    --toc --toc-depth=1 --number-sections \
    --variable=lang:pl --variable=geometry:margin=2.5cm \
    --variable=fontsize:11pt --variable=colorlinks:true

case "$ENGINE" in
    xelatex|lualatex)
        set -- "$@" --variable=mainfont:"DejaVu Serif" --variable=sansfont:"DejaVu Sans"
        ;;
esac

if [ -f "$LOGO" ]; then
    set -- "$@" --variable=titlegraphic:"$LOGO"
    echo "Using logo: $LOGO"
else
    echo "No logo at $LOGO (set KRAMO_LOGO to override); building without it."
fi

pandoc "$@"

echo "Manual written: $OUTPUT"
