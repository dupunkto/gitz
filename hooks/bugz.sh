#!/bin/sh -e

. /volume1/docker/.env

ZERO="0000000000000000000000000000000000000000"

repo_dir=$(git rev-parse --git-dir)
repo_dir=$(cd "$repo_dir" && pwd -P)
repo_name=$(basename "$repo_dir" .git)
namespace=$(basename "$(dirname "$repo_dir")")

while IFS=' ' read -r old new ref; do
  [ "$new" = "$ZERO" ] && continue

  if [ "$old" = "$ZERO" ]; then
    exclude=$(git for-each-ref --format='^%(objectname)' refs/heads/ refs/tags/ | grep -vF "^$new")
    # shellcheck disable=SC2086
    revs=$(git rev-list "$new" $exclude 2>/dev/null)
  else
    revs=$(git rev-list "$old..$new")
  fi

  echo "$revs" | while IFS= read -r rev; do
    [ -z "$rev" ] && continue

    author=$(git log --format="%aN <%aE>" -1 "$rev")
    message=$(git log --format="%B" -1 "$rev")

    curl -fsS \
      -H "Authorization: Bearer $BUGZ_TOKEN" \
      --data-urlencode "namespace=$namespace" \
      --data-urlencode "repo=$repo_name" \
      --data-urlencode "author=$author" \
      --data-urlencode "rev=$rev" \
      --data-urlencode "message=$message" \
      "https://bugs.dupunkto.org/api/commit" || true
  done
done
