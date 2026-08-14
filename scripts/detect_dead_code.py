#!/usr/bin/env python3
"""
detect_dead_code.py — cross-verified relation-graph scanner for dead/unused
code in this Laravel app: Blade views, Eloquent models, migrations, and
controller actions.

Usage:
    python scripts/detect_dead_code.py [--out docs/dead_code_report.md]

Why "cross-verified" instead of just "scanned once":
    Re-running the *same* deterministic regex against the *same* files
    produces the *same* result every time - there is nothing probabilistic
    to average out, so looping one check N times buys zero extra confidence.
    What actually reduces false positives is asking the question multiple
    INDEPENDENT ways and only trusting a "dead" verdict when every method
    agrees. So every symbol below is checked with several structurally
    different detectors (a direct string search, a reverse-index built by
    parsing every call site in the repo into a set, a filename-only search,
    an import-statement parse, an instantiation parse, ...). A symbol is
    only flagged if ALL of its independent checks agree nothing references
    it. The report shows exactly which checks ran and how many passed.

Algorithm:
  1. The whole repo is loaded as LINES (not just raw text) per file, so every
     symbol is defined at an exact file:line, and a relation search can
     exclude just that one defining line (not the whole file) - so a private
     helper method calling itself, or a migration's own down() repeating its
     own table name, is still correctly recognized as a real relation.

  2. For each category, every symbol is defined:
         - a Blade view          -> its Laravel view key
         - a model class         -> its `class X` declaration line
         - a migration table     -> its `Schema::create(...)` line
         - a controller method   -> its `function name(...)` line

  3. Each symbol runs through several independent "does anything relate to
     this?" checks (see CHECKS in each scan_* function). Only if every
     check independently finds nothing is the symbol flagged.

  4. Flagged symbols get corroborating evidence attached automatically:
     the file's leading comment (often states "demo only" / "used to live
     in..." / "deprecated" in this codebase) and its git history (last
     commit touching it, or "never committed" for a brand-new file that
     might just be work in progress, not dead).

This is still a HEURISTIC, not proof - it cannot see through dynamically
built strings, reflection, or Laravel's implicit conventions. Read the
evidence for each flagged item before deleting it; this app moves real
money (wallets/deposits/withdrawals).
"""

import argparse
import os
import re
import subprocess
from datetime import datetime
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

EXCLUDE_DIRS = {
    ".git", "vendor", "node_modules", "storage", "bootstrap", "logs",
    ".claude", "public",
}

CONTROLLER_METHOD_DENYLIST = {
    "__construct", "__invoke", "__toString", "__get", "__set",
    "__call", "__callStatic", "boot", "register",
}

NOTE_KEYWORDS = [
    "demo", "preview", "deprecated", "legacy", "todo", "not yet", "unused",
    "dead", "used to", "old", "stale", "wip", "work in progress", "obsolete",
    "no longer", "temporary", "placeholder",
]


# ---------------------------------------------------------------------------
# Corpus
# ---------------------------------------------------------------------------

class Corpus:
    def __init__(self, root: Path):
        self.lines = {}
        self.full_text = {}
        for f in self._iter_files(root):
            text = f.read_text(encoding="utf-8", errors="ignore")
            ls = text.splitlines()
            self.lines[f] = ls
            self.full_text[f] = "\n".join(ls)

    @staticmethod
    def _iter_files(root: Path):
        for dirpath, dirnames, filenames in os.walk(root):
            dirnames[:] = [d for d in dirnames if d not in EXCLUDE_DIRS and not d.startswith(".")]
            for fn in filenames:
                if fn.endswith(".php"):
                    yield Path(dirpath) / fn

    def text_excluding(self, definition_path=None, excluded_lines=None, excluded_dirs=None):
        """Yield (path, text) for every file, with excluded_lines blanked out
        of definition_path's text and files under excluded_dirs skipped."""
        excluded_lines = excluded_lines or set()
        excluded_dirs = excluded_dirs or []
        for path, text in self.full_text.items():
            if excluded_dirs and any(d in path.parents for d in excluded_dirs):
                continue
            if path == definition_path and excluded_lines:
                ls = self.lines[path]
                text = "\n".join(l for i, l in enumerate(ls, start=1) if i not in excluded_lines)
            yield path, text

    def any_match(self, patterns, definition_path=None, excluded_lines=None, excluded_dirs=None):
        for _, text in self.text_excluding(definition_path, excluded_lines, excluded_dirs):
            for pat in patterns:
                if pat.search(text):
                    return True
        return False

    def find_all(self, pattern):
        """Run `pattern` (with one capture group) over every file, return
        the set of captured values repo-wide. Used to build reverse-indexes
        for independent, differently-shaped verification checks."""
        out = set()
        for _, text in self.full_text.items():
            for m in pattern.finditer(text):
                out.add(m.group(1))
        return out


def rel(path: Path) -> str:
    return str(path.relative_to(ROOT)).replace("\\", "/")


# ---------------------------------------------------------------------------
# Evidence: leading comment + git history, attached to every flagged item
# ---------------------------------------------------------------------------

def leading_comment_note(lines):
    head = "\n".join(lines[:12]).lower()
    hits = sorted({kw for kw in NOTE_KEYWORDS if kw in head})
    return ", ".join(hits) if hits else None


_GIT_AVAILABLE = None


def git_info(path: Path):
    global _GIT_AVAILABLE
    if _GIT_AVAILABLE is False:
        return "git unavailable"
    try:
        r = subprocess.run(
            ["git", "log", "-1", "--format=%h %ad %s", "--date=short", "--", str(path)],
            cwd=ROOT, capture_output=True, text=True, timeout=10,
        )
        _GIT_AVAILABLE = True
    except (OSError, subprocess.SubprocessError):
        _GIT_AVAILABLE = False
        return "git unavailable"
    out = r.stdout.strip()
    return out if out else "never committed (new/untracked)"


def attach_evidence(item, path, lines):
    item["comment_note"] = leading_comment_note(lines)
    item["git"] = git_info(path)
    return item


# ---------------------------------------------------------------------------
# 1. Views — checks: exact quoted key / reverse-indexed call-site parse /
#            bare filename mention / component tag / dynamic component attr
# ---------------------------------------------------------------------------

VIEW_CALL_RE = re.compile(
    r"""(?:\bview|View::make|->view|@extends|@include\w*|@component|->markdown|component\s*=)\s*\(?\s*['"]([^'"]+)['"]"""
)

ALWAYS_USED_VIEW_KEYS = {"welcome", "maintenance"}


def module_view_key(views_root, module_name, blade_file):
    r = str(blade_file.relative_to(views_root))[: -len(".blade.php")]
    return f"{module_name}::" + r.replace("\\", ".").replace("/", ".")


def resources_view_key(views_root, blade_file):
    r = str(blade_file.relative_to(views_root))[: -len(".blade.php")]
    return r.replace("\\", ".").replace("/", ".")


def find_views():
    views = []
    modules_dir = ROOT / "app" / "Modules"
    if modules_dir.is_dir():
        for module_dir in sorted(modules_dir.iterdir()):
            views_root = module_dir / "Views"
            if module_dir.is_dir() and views_root.is_dir():
                for blade_file in views_root.rglob("*.blade.php"):
                    key = module_view_key(views_root, module_dir.name, blade_file)
                    views.append({"path": blade_file, "key": key, "tag": None})

    res_root = ROOT / "resources" / "views"
    if res_root.is_dir():
        for blade_file in res_root.rglob("*.blade.php"):
            key = resources_view_key(res_root, blade_file)
            tag = key[len("components."):].replace(".", "-") if key.startswith("components.") else None
            views.append({"path": blade_file, "key": key, "tag": tag})
    return views


def scan_views(corpus):
    views = find_views()
    referenced_keys = corpus.find_all(VIEW_CALL_RE)  # reverse-index, independent of direct search below

    unused = []
    for v in views:
        short_key = v["key"].split("::")[-1]
        if short_key in ALWAYS_USED_VIEW_KEYS:
            continue

        path, key, tag = v["path"], v["key"], v["tag"]
        all_lines = set(range(1, len(corpus.lines[path]) + 1))
        bare_name = key.split(".")[-1].split("::")[-1]

        checks = []
        checks.append(("exact quoted key `'{}'`".format(key),
                        corpus.any_match([re.compile(r"""['"]""" + re.escape(key) + r"""['"]""")],
                                          path, excluded_lines=all_lines)))
        checks.append(("reverse-indexed call-site parse (view()/@extends/@include/@component/...)",
                        key in referenced_keys))
        checks.append(("bare filename `{}` mentioned anywhere".format(bare_name),
                        corpus.any_match([re.compile(r"\b" + re.escape(bare_name) + r"\b")],
                                          path, excluded_lines=all_lines)))
        if tag:
            checks.append(("`<x-{}>` component tag".format(tag),
                            corpus.any_match([re.compile(r"<x[-:]" + re.escape(tag) + r"[\s/>]")],
                                              path, excluded_lines=all_lines)))
            checks.append(("dynamic `component=\"{}\"`".format(key),
                            corpus.any_match([re.compile(r"""component\s*=\s*['"]""" + re.escape(key) + r"""['"]""")],
                                              path, excluded_lines=all_lines)))

        if not any(found for _, found in checks):
            item = {"path": path, "key": key, "checks": checks}
            unused.append(attach_evidence(item, path, corpus.lines[path]))
    return views, unused


# ---------------------------------------------------------------------------
# 2. Models — checks: word-boundary class name / `use App\Models\X` imports /
#            `new X(` instantiations / `X::` static-call prefixes / factory
# ---------------------------------------------------------------------------

CLASS_RE = re.compile(r"^\s*(?:final\s+|abstract\s+)?class\s+(\w+)")
TABLE_PROP_RE = re.compile(r"""protected\s+\$table\s*=\s*['"]([a-zA-Z0-9_]+)['"]""")
USE_MODEL_RE = re.compile(r"use\s+App\\Models\\(\w+)\s*;")
NEW_INSTANCE_RE = re.compile(r"\bnew\s+(\w+)\s*\(")
STATIC_PREFIX_RE = re.compile(r"\b(\w+)::")


def find_models():
    models_dir = ROOT / "app" / "Models"
    models = []
    if not models_dir.is_dir():
        return models
    for f in sorted(models_dir.glob("*.php")):
        lines = f.read_text(encoding="utf-8", errors="ignore").splitlines()
        for i, line in enumerate(lines, start=1):
            m = CLASS_RE.match(line)
            if m:
                text = "\n".join(lines)
                tbl = TABLE_PROP_RE.search(text)
                models.append({
                    "path": f, "class": m.group(1), "line": i,
                    "table": tbl.group(1) if tbl else None,
                })
                break
    return models


def scan_models(corpus, models):
    imported = corpus.find_all(USE_MODEL_RE)
    instantiated = corpus.find_all(NEW_INSTANCE_RE)
    static_called = corpus.find_all(STATIC_PREFIX_RE)
    factories_dir = ROOT / "database" / "factories"
    factory_classes = {f.stem for f in factories_dir.glob("*.php")} if factories_dir.is_dir() else set()

    unused = []
    for m in models:
        cls, path = m["class"], m["path"]
        checks = [
            ("word-boundary class name search", corpus.any_match(
                [re.compile(r"\b" + re.escape(cls) + r"\b")], path, excluded_lines={m["line"]})),
            ("reverse-indexed `use App\\Models\\{}`".format(cls), cls in imported),
            ("reverse-indexed `new {}(`".format(cls), cls in instantiated),
            ("reverse-indexed `{}::...` static prefix".format(cls), cls in static_called),
            ("matching `{}Factory`".format(cls), f"{cls}Factory" in factory_classes),
        ]
        if not any(found for _, found in checks):
            item = {"path": path, "class": cls, "line": m["line"], "checks": checks}
            unused.append(attach_evidence(item, path, corpus.lines[path]))
    return unused


# ---------------------------------------------------------------------------
# 3. Migrations — checks: quoted table name outside migrations dir /
#            referenced by a later migration (schema still evolving) /
#            model coverage (pre-filter, not a per-item check)
# ---------------------------------------------------------------------------

CREATE_RE = re.compile(r"""Schema::create\(\s*['"]([a-zA-Z0-9_]+)['"]""")
TABLE_TOUCH_RE = re.compile(
    r"""(?:DB::table|->table|Schema::table|Schema::drop(?:IfExists)?|\$table\s*=)\s*\(?\s*['"]([a-zA-Z0-9_]+)['"]"""
)

BUILTIN_TABLES = {
    "users", "password_reset_tokens", "sessions", "cache", "cache_locks",
    "jobs", "job_batches", "failed_jobs", "migrations",
}


def camel_to_snake_plural(class_name: str) -> str:
    snake = re.sub(r"(?<!^)(?=[A-Z])", "_", class_name).lower()
    if snake.endswith("y") and not snake.endswith(("ay", "ey", "iy", "oy", "uy")):
        return snake[:-1] + "ies"
    if snake.endswith(("s", "x", "ch", "sh")):
        return snake + "es"
    return snake + "s"


def find_migration_tables():
    mig_dir = ROOT / "database" / "migrations"
    creates = []
    if not mig_dir.is_dir():
        return creates, mig_dir
    for f in sorted(mig_dir.glob("*.php")):
        lines = f.read_text(encoding="utf-8", errors="ignore").splitlines()
        for i, line in enumerate(lines, start=1):
            m = CREATE_RE.search(line)
            if m:
                creates.append({"path": f, "table": m.group(1), "line": i})
    return creates, mig_dir


def scan_migrations(corpus, models):
    creates, mig_dir = find_migration_tables()

    known_tables = set(BUILTIN_TABLES)
    for m in models:
        known_tables.add(m["table"] if m["table"] else camel_to_snake_plural(m["class"]))

    touched_everywhere = corpus.find_all(TABLE_TOUCH_RE)  # reverse-index (any file, any Schema::table/DB::table/etc.)

    orphaned = []
    for c in creates:
        table, path = c["table"], c["path"]
        if table in known_tables:
            continue

        outside_app_pattern = re.compile(r"""['"]""" + re.escape(table) + r"""['"]""")
        checks = [
            ("quoted table name outside database/migrations/", corpus.any_match(
                [outside_app_pattern], path, excluded_dirs=[mig_dir])),
            ("model $table/naming-convention coverage", False),  # pre-filtered above; kept visible for the report
            ("reverse-indexed DB::table()/->table()/Schema::table()/$table= anywhere", table in touched_everywhere),
        ]
        if not any(found for _, found in checks):
            item = {"path": path, "table": table, "line": c["line"], "checks": checks}
            orphaned.append(attach_evidence(item, path, corpus.lines[path]))
    return creates, orphaned


# ---------------------------------------------------------------------------
# 4. Controller actions — checks: route array-callable / generic method call /
#            reverse-indexed (class,method) route pairs / interface exclusion
# ---------------------------------------------------------------------------

METHOD_RE = re.compile(r"^\s*(?:public|protected|private)\s+(?:static\s+)?function\s+(\w+)\s*\(")
ROUTE_PAIR_RE = re.compile(r"(\w+)::class\s*,\s*['\"](\w+)['\"]")


def find_controller_methods():
    methods = []
    for controllers_dir in (ROOT / "app" / "Modules").glob("*/Controllers"):
        for f in sorted(controllers_dir.glob("*.php")):
            cls_match, implements = None, False
            lines = f.read_text(encoding="utf-8", errors="ignore").splitlines()
            for i, line in enumerate(lines, start=1):
                cm = CLASS_RE.match(line)
                if cm:
                    cls_match = cm.group(1)
                    implements = "implements" in line
                mm = METHOD_RE.match(line)
                if mm and mm.group(1) not in CONTROLLER_METHOD_DENYLIST:
                    methods.append({
                        "path": f, "class": cls_match, "method": mm.group(1), "line": i,
                        "implements": implements,
                    })
    return methods


def scan_controller_methods(corpus):
    methods = find_controller_methods()
    route_pairs = set()
    for text in corpus.full_text.values():
        for m in ROUTE_PAIR_RE.finditer(text):
            route_pairs.add((m.group(1), m.group(2)))

    unused = []
    for meth in methods:
        cls, name, path = meth["class"], meth["method"], meth["path"]

        if meth["implements"]:
            # Class implements an interface - contract methods can be invoked
            # polymorphically through the interface type, invisible to a text
            # search. Too high a false-positive risk, so skip entirely.
            continue

        checks = [
            ("reverse-indexed `[{}::class, '{}']` route pair".format(cls, name),
             (cls, name) in route_pairs),
            ("generic `->{}(` / `::{}(` call anywhere".format(name, name),
             corpus.any_match([re.compile(r"(?:->|::)\s*" + re.escape(name) + r"\s*\(")],
                               path, excluded_lines={meth["line"]})),
        ]
        if not any(found for _, found in checks):
            item = {"path": path, "class": cls, "method": name, "line": meth["line"], "checks": checks}
            unused.append(attach_evidence(item, path, corpus.lines[path]))
    return methods, unused


# ---------------------------------------------------------------------------
# Report
# ---------------------------------------------------------------------------

def checks_block(item):
    lines = []
    for name, found in item["checks"]:
        mark = "found nothing" if found is False else ("N/A" if found is None else "FOUND (bug?)")
        lines.append(f"  - {name} → {mark}")
    return "\n".join(lines)


def evidence_block(item):
    lines = []
    if item.get("comment_note"):
        lines.append(f"  - Leading-comment keywords: *{item['comment_note']}*")
    if item.get("git"):
        lines.append(f"  - Git history: `{item['git']}`")
    return "\n".join(lines)


def write_report(out_path, views, unused_views, models, unused_models,
                  creates, orphaned_migrations, methods, unused_methods):
    L = []
    L.append("# Dead Code Report")
    L.append("")
    L.append(f"_Generated {datetime.now().strftime('%Y-%m-%d %H:%M')} by `scripts/detect_dead_code.py`._")
    L.append("")
    L.append(
        "Every item below passed **every one** of several independent, structurally different "
        "checks with no relation found (a direct string search, a reverse-index built by parsing "
        "every call site in the repo, a filename search, an import/instantiation parse, etc.) — "
        "see the collapsed check list under each item. This is still a **heuristic, not proof**: "
        "dynamically built strings, reflection, and Laravel's implicit conventions can hide a real "
        "usage from any text scan. Each item also carries its leading-comment keywords and git "
        "history as corroborating evidence, the same way you'd manually check it. "
        "**Read the evidence before deleting anything** — this app moves real money "
        "(wallets/deposits/withdrawals)."
    )
    L.append("")
    L.append("## Summary")
    L.append("")
    L.append(f"- Views: {len(views)} scanned — **{len(unused_views)}** flagged (all independent checks agreed)")
    L.append(f"- Models: {len(models)} scanned — **{len(unused_models)}** flagged")
    L.append(f"- Migration tables: {len(creates)} scanned — **{len(orphaned_migrations)}** flagged")
    L.append(f"- Controller actions: {len(methods)} scanned — **{len(unused_methods)}** flagged")
    L.append("")

    def section(title, items, id_fn, extra=""):
        L.append(f"## {title}")
        L.append("")
        if not items:
            L.append("None found.")
            L.append("")
            return
        if extra:
            L.append(extra)
            L.append("")
        for i, item in enumerate(items, 1):
            L.append(f"**{i}. {id_fn(item)}** — `{rel(item['path'])}:{item.get('line', '')}`".rstrip(":"))
            L.append("")
            L.append(checks_block(item))
            ev = evidence_block(item)
            if ev:
                L.append(ev)
            L.append("")

    section(
        "Possibly unused views", unused_views,
        lambda v: f"`{v['key']}`",
    )
    section(
        "Possibly unused models", unused_models,
        lambda m: f"`{m['class']}`",
    )
    section(
        "Possibly orphaned migration tables", orphaned_migrations,
        lambda o: f"`{o['table']}`",
        extra="`Schema::create()` builds a table with no model coverage and no reference anywhere else in the app.",
    )
    section(
        "Possibly unreachable controller actions", unused_methods,
        lambda m: f"`{m['class']}::{m['method']}`",
        extra="No route wires this action and nothing calls it anywhere in the repo. "
              "Methods on classes that `implements` an interface are skipped entirely "
              "(too easy to false-positive on polymorphic dispatch).",
    )

    L.append("## Next steps")
    L.append("")
    L.append("- [ ] Read the evidence block for each item — it's context, not a verdict.")
    L.append("- [ ] Cross-check against MEMORY.md before removing anything that might back a feature still under construction.")
    L.append("- [ ] Re-run this script after cleanup to confirm flagged items are gone.")
    L.append("")

    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text("\n".join(L), encoding="utf-8")


def main():
    parser = argparse.ArgumentParser(description="Cross-verified relation-graph scan for dead code.")
    parser.add_argument("--out", default="docs/dead_code_report.md")
    args = parser.parse_args()

    print("Indexing repo (line-level corpus)...")
    corpus = Corpus(ROOT)
    print(f"  {len(corpus.lines)} PHP/Blade files, {sum(len(v) for v in corpus.lines.values())} lines")

    print("Scanning views (5 independent checks per view)...")
    views, unused_views = scan_views(corpus)
    print(f"  {len(views)} views, {len(unused_views)} flagged")

    print("Scanning models (5 independent checks per model)...")
    models = find_models()
    unused_models = scan_models(corpus, models)
    print(f"  {len(models)} models, {len(unused_models)} flagged")

    print("Scanning migrations (3 independent checks per table)...")
    creates, orphaned_migrations = scan_migrations(corpus, models)
    print(f"  {len(creates)} created tables, {len(orphaned_migrations)} flagged")

    print("Scanning controller actions (2 independent checks per action)...")
    methods, unused_methods = scan_controller_methods(corpus)
    print(f"  {len(methods)} actions, {len(unused_methods)} flagged")

    out_path = (ROOT / args.out).resolve()
    write_report(out_path, views, unused_views, models, unused_models,
                 creates, orphaned_migrations, methods, unused_methods)
    print(f"\nReport written to {rel(out_path)}")


if __name__ == "__main__":
    main()
