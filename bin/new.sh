#!/bin/sh
# Usage: ./new.sh <namespace> <repo>

cd "$1" || exit 1
git init --bare --shared=group "$2"
git -C "$2" config receive.denyNonFastForwards false

config="$2/config"

case "$1" in
  sites|axcelott) org=RobinBoers ;;
  *) org=$1 ;;
esac

cat <<EOL >> "$config"
[remote "github"]
        url = git@github.com:$org/$2.git
        mirror = true
        push = +refs/heads/*:refs/heads/*
        push = +refs/tags/*:refs/tags/*

[remote "codeberg"]
        url = git@codeberg.org:$org/$2.git
        mirror = true
        push = +refs/heads/*:refs/heads/*
        push = +refs/tags/*:refs/tags/*
EOL

post_receive="$2/hooks/post-receive"

cat <<EOL > "$post_receive"
#!/bin/bash
/volume1/git/mirror.sh github
/volume1/git/mirror.sh codeberg
EOL

chmod +x "$post_receive"

post_update="$2/hooks/post-update"

cat <<EOL > "$post_update"
#!/bin/bash
git update-server-info
EOL

chmod +x "$post_update"

chmod -R g+rwX "$2"
find "$2" -type d -exec chmod g+s {} +
sudo chown -R gitwastaken:33 "$2"

echo "https://github.com/new"
echo "https://codeberg.org/repo/create"
