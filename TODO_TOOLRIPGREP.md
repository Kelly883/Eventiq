# ripgrep binary setup

## Goal
Unblock code search for future steps (search_files tool requires `rg`).

## Current issue
`search_files` fails: **Could not find ripgrep binary**.

## Plan
- Detect `rg` availability.
- If missing, download/install a Windows ripgrep binary into a repo-local path.
- Update PATH (within project tooling or provide a wrapper script).

(Implementation pending.)

