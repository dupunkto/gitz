# Gitz

When getting errors about dubious ownership, run:

```shell
git config --system --add safe.directory '*'
```

Sometimes it also crashes because the `HEAD` file in a bare repo contains
a non-existant branch (often `master` while the it should be `trunk` or `main`).
