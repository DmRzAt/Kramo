#!/bin/sh

set -eu

# Rebuild the self-hosted variable fonts as Latin + Polish subsets.
# Requires fonttools (pip install fonttools brotli).

export MSYS_NO_PATHCONV=1
export MSYS2_ARG_CONV_EXCL='*'

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
FONT_DIR="$ROOT_DIR/theme/kramo-child/assets/fonts"

# source:target:pinned-axes triples, one per self-hosted family. Pinning the
# axes a design never varies keeps the display face from tripling in weight.
FONTS="InterVariable-full.woff2:InterVariable.woff2:
InstrumentSans-full.ttf:InstrumentSans.woff2:wdth=100
BricolageGrotesque-full.ttf:BricolageGrotesque.woff2:wdth=100 opsz=36"

# Basic Latin + Latin-1 punctuation, the Polish diacritics and the typographic
# quotes/dashes used in the Polish interface strings.
UNICODES='U+0000-00FF,U+0104-0107,U+010C-010D,U+0118-0119,U+0141-0144,U+015A-015B,U+0160-0161,U+0179-017C,U+017D-017E,U+2013-2014,U+2018-201A,U+201C-201E,U+2026,U+20AC,U+2122'

# A Windows-native Python cannot read MSYS-style paths, so translate when the
# helper is available. On Linux/macOS the paths pass through unchanged.
to_python_path() {
    if command -v cygpath >/dev/null 2>&1; then
        cygpath -w "$1"
    else
        printf '%s' "$1"
    fi
}

echo "$FONTS" | while IFS=: read -r source target pins; do
    [ -n "$source" ] || continue

    source_path="$FONT_DIR/$source"
    target_path="$FONT_DIR/$target"

    if [ ! -f "$source_path" ]; then
        echo "Skipping $target: missing $source_path" >&2
        continue
    fi

    subset_source="$source_path"
    if [ -n "$pins" ]; then
        pinned_path="$FONT_DIR/.pinned-$target.ttf"
        # shellcheck disable=SC2086
        python -m fontTools.varLib.instancer "$(to_python_path "$source_path")" $pins \
            -o "$(to_python_path "$pinned_path")" >/dev/null
        subset_source="$pinned_path"
    fi

    python -m fontTools.subset "$(to_python_path "$subset_source")" \
        --output-file="$(to_python_path "$target_path")" \
        --flavor=woff2 \
        --layout-features='kern,liga,clig,calt,tnum' \
        --unicodes="$UNICODES" \
        --name-IDs='0,1,2,3,4,5,6' \
        --drop-tables+=DSIG \
        --no-hinting \
        --desubroutinize

    rm -f "$FONT_DIR/.pinned-$target.ttf"

    echo "Subset written: $target ($(du -h "$target_path" | cut -f1))"
done
