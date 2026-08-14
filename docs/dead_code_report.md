# Dead Code Report

_Generated 2026-08-14 22:13 by `scripts/detect_dead_code.py`._

Every item below passed **every one** of several independent, structurally different checks with no relation found (a direct string search, a reverse-index built by parsing every call site in the repo, a filename search, an import/instantiation parse, etc.) — see the collapsed check list under each item. This is still a **heuristic, not proof**: dynamically built strings, reflection, and Laravel's implicit conventions can hide a real usage from any text scan. Each item also carries its leading-comment keywords and git history as corroborating evidence, the same way you'd manually check it. **Read the evidence before deleting anything** — this app moves real money (wallets/deposits/withdrawals).

## Summary

- Views: 72 scanned — **0** flagged (all independent checks agreed)
- Models: 22 scanned — **0** flagged
- Migration tables: 30 scanned — **0** flagged
- Controller actions: 158 scanned — **0** flagged

## Possibly unused views

None found.

## Possibly unused models

None found.

## Possibly orphaned migration tables

None found.

## Possibly unreachable controller actions

None found.

## Next steps

- [ ] Read the evidence block for each item — it's context, not a verdict.
- [ ] Cross-check against MEMORY.md before removing anything that might back a feature still under construction.
- [ ] Re-run this script after cleanup to confirm flagged items are gone.
