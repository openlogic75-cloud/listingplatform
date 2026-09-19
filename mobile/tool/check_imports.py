#!/usr/bin/env python3
"""Resolve every relative import in the Flutter app.

Flutter is not installed in this workspace, so `flutter analyze` cannot run.
This script is the stand-in for the "does it even link" question: it walks
lib/ and test/, resolves each relative import/export against the file system,
and reports anything that points at a missing file.
"""
import glob
import os
import re
import sys

IMPORT_RE = re.compile(r"""^\s*(?:import|export)\s+['"]([^'"]+)['"]""")


def main() -> int:
    roots = ["lib", "test"]
    files = []
    for root in roots:
        files += sorted(glob.glob(f"{root}/**/*.dart", recursive=True))

    problems = []

    for path in files:
        with open(path, encoding="utf-8") as handle:
            lines = handle.read().splitlines()

        for number, line in enumerate(lines, start=1):
            match = IMPORT_RE.match(line)
            if not match:
                continue

            target = match.group(1)
            if not target.startswith("."):
                continue  # package: or dart: - resolved by pub, not the fs

            resolved = os.path.normpath(os.path.join(os.path.dirname(path), target))
            if not os.path.isfile(resolved):
                problems.append(f"{path}:{number} -> {target} (missing {resolved})")

    for problem in problems:
        print(f"UNRESOLVED {problem}")

    print(f"checked {len(files)} dart files, {len(problems)} unresolved imports")
    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(main())
