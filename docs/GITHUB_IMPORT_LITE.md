# GitHub Import Lite

This package is the GitHub-safe source import. Large generated PNG and WAV assets were replaced with small placeholders while preserving the exact file paths used by the PHP pages.

## Push this package to the new repo

```bash
unzip stonefellow-github-source-lite.zip -d stonefellow-github-source-lite
cd stonefellow-github-source-lite/stonefellow

git init
git branch -M platform-foundation-import
git remote add origin https://github.com/bigriversocial74/stonefellow-membership-platform.git

git fetch origin main
git add .
git commit -m "Initial Stonefellow membership platform foundation"
git push -u origin platform-foundation-import
```

Then open a PR from `platform-foundation-import` into `main`.

## Production media

The repo-lite package is designed for GitHub import and code review. For launch, replace placeholder media files with final production assets, or upload the full deploy ZIP media folders directly to the server.
