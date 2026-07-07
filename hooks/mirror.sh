#!/bin/sh

. /volume1/git/.env

remote="$1"

git push --mirror "$remote"

case "$remote" in
    github)
        api_base="https://api.github.com"
        token="$GITHUB_TOKEN"
        auth="Bearer"
        home_field="homepage"
        ;;
    codeberg)
        api_base="https://codeberg.org/api/v1"
        token="$CODEBERG_TOKEN"
        auth="token"
        home_field="website"
        ;;
    *)
        exit 0
        ;;
esac

url="$(git config --get "remote.$remote.url")" || exit 0

case "$url" in
    git@*)     slug="$(printf '%s' "$url" | sed 's/^[^:]*://; s/\.git$//')" ;;
    https://*) slug="$(printf '%s' "$url" | sed 's|^https://[^/]*/||; s|\.git$||')" ;;
    *) exit 0 ;;
esac

desc="$(cat "$GIT_DIR/description")"

# Ensure it ends with a .
case "$desc" in
    *.) ;;
    *) desc="$desc." ;;
esac

escaped_desc="$(printf '%s' "$desc" | sed 's/\\/\\\\/g; s/"/\\"/g')"
body="{\"description\":\"$escaped_desc\""

if [ -f "$GIT_DIR/homepage-url" ]; then
    home="$(cat "$GIT_DIR/homepage-url")"
    escaped_home="$(printf '%s' "$home" | sed 's/\\/\\\\/g; s/"/\\"/g')"
    body="$body,\"$home_field\":\"$escaped_home\""
fi

body="$body}"

curl -sf -X PATCH \
    -H "Authorization: $auth $token" \
    -H "Content-Type: application/json" \
    -d "$body" \
    "$api_base/repos/$slug" >/dev/null
