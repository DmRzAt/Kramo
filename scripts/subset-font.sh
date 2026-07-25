#!/bin/sh

set -eu

# Rebuild the theme's variable font as a Latin + Polish subset.
# Requires fonttools (pip install fonttools brotli).

export MSYS_NO_PATHCONV=1
export MSYS2_ARG_CONV_EXCL='*'

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
FONT_DIR="$ROOT_DIR/theme/kramo-child/assets/fonts"
SOURCE="$FONT_DIR/InterVariable-full.woff2"
TARGET="$FONT_DIR/InterVariable.woff2"

if [ ! -f "$SOURCE" ]; then
    echo "Missing $SOURCE — keep the unsubsetted original next to the subset." >&2
    exit 1
fi

# Basic Latin + Latin-1 punctuation, the Polish diacritics and the typographic
# quotes/dashes used in the Polish interface strings.
UNICODES='U+0000-00FF,U+0104-0107,U+010C-010D,U+0118-0119,U+0141-0144,U+015A-015B,U+0160-0161,U+0179-017C,U+017D-017E,U+2013-2014,U+2018-201A,U+201C-201E,U+2026,U+20AC,U+2122'

# A Windows-native Python cannot read MSYS-style paths, so translate when the
# helper is available. On Linux/macOS the paths are passed through unchanged.
if command -v cygpath >/dev/null 2>&1; then
    SOURCE_ARG="$(cygpath -w "$SOURCE")"
    TARGET_ARG="$(cygpath -w "$TARGET")"
else
    SOURCE_ARG="$SOURCE"
    TARGET_ARG="$TARGET"
fi

python -m fontTools.subset "$SOURCE_ARG" \
    --output-file="$TARGET_ARG" \
    --flavor=woff2 \
    --layout-features='kern,liga,clig,calt,tnum' \
    --unicodes="$UNICODES" \
    --name-IDs='0,1,2,3,4,5,6' \
    --drop-tables+=DSIG \
    --no-hinting \
    --desubroutinize

echo "Subset written: $TARGET"
ls -l "$SOURCE" "$TARGET"
