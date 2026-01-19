#!/bin/bash
# Shared helpers for install/update scripts

draw_bar() {
    local pct="$1"
    local width="${2:-24}"

    if ! [[ "$pct" =~ ^[0-9]+$ ]]; then
        pct=0
    fi

    [ "$pct" -lt 0 ] && pct=0
    [ "$pct" -gt 100 ] && pct=100

    local filled=$((pct * width / 100))
    local empty=$((width - filled))

    local bar="" i=0
    while [ $i -lt $filled ]; do bar="${bar}#"; i=$((i+1)); done
    i=0
    while [ $i -lt $empty ]; do bar="${bar}-"; i=$((i+1)); done

    printf "[%s] %3s%%" "$bar" "$pct"
}

get_content_length() {
    local url="$1"
    local len=""

    if command -v curl >/dev/null 2>&1; then
        len="$(curl -sIL "$url" | awk '
            BEGIN{IGNORECASE=1}
            /^content-length:/ {gsub("\r",""); print $2}
        ' | tail -n 1)"
    elif command -v wget >/dev/null 2>&1; then
        len="$(wget --spider -S "$url" 2>&1 | awk '
            BEGIN{IGNORECASE=1}
            /Content-Length:/ {print $2}
        ' | tail -n 1)"
    fi

    local clean_len
    clean_len="$(printf "%s" "$len" | tr -cd '0-9')"
    printf "%s" "$clean_len"
}

indeterminate_bar() {
    local pos="$1"
    local width="${2:-24}"

    local bar="" i=0
    while [ $i -lt $width ]; do
        if [ $i -eq "$pos" ]; then
            bar="${bar}#"
        else
            bar="${bar}-"
        fi
        i=$((i+1))
    done

    printf "[%s]" "$bar"
}

download_with_loader() {
    local url="$1"
    local out="$2"
    local method="" pid="" total="" width=24
    local cur=0 pct=0
    local pos=0 dir=1 max=0

    if command -v curl >/dev/null 2>&1; then
        method="curl"
        curl -fsSL "$url" -o "$out" &
        pid=$!
    elif command -v wget >/dev/null 2>&1; then
        method="wget"
        wget -q "$url" -O "$out" &
        pid=$!
    else
        echo "Error: Neither curl nor wget is available. Cannot proceed."
        return 127
    fi

    total="$(get_content_length "$url")"

    if [ -t 1 ]; then
        if [ -n "$total" ] && [ "$total" -gt 0 ] 2>/dev/null; then
            while kill -0 "$pid" 2>/dev/null; do
                cur=0
                pct=0
                [ -f "$out" ] && cur="$(wc -c < "$out" 2>/dev/null || echo 0)"
                if [ "$total" -gt 0 ] 2>/dev/null; then
                    pct=$((cur * 100 / total))
                else
                    pct=0
                fi
                [ "$pct" -gt 99 ] && pct=99
                printf "\rUsing %s... %s" "$method" "$(draw_bar "$pct" "$width")"
                sleep 0.2
            done
        else
            pos=0
            dir=1
            max=$((width - 1))
            while kill -0 "$pid" 2>/dev/null; do
                printf "\rUsing %s... %s" "$method" "$(indeterminate_bar "$pos" "$width")"
                pos=$((pos + dir))
                [ "$pos" -ge "$max" ] && dir=-1
                [ "$pos" -le 0 ] && dir=1
                sleep 0.08
            done
        fi
    fi

    wait "$pid"
    local rc=$?

    if [ "$rc" -ne 0 ]; then
        [ -t 1 ] && printf "\rUsing %s... %s\n" "$method" "$(draw_bar 0 "$width")"
        echo "Error: Failed to download package using $method."
        return "$rc"
    fi

    [ -t 1 ] && printf "\rUsing %s... %s\n" "$method" "$(draw_bar 100 "$width")"
    return 0
}
