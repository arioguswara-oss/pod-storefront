#!/usr/bin/env python3
"""Build two standalone WordPress ZIPs using only Python's standard library."""

import argparse
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile, ZipInfo


ROOT = Path(__file__).resolve().parents[1]
COMPONENTS = (
    ("themes", "pod-store-theme", {"style.css": "Theme Name: POD Store Theme", "index.php": None, "functions.php": None, "header.php": None, "footer.php": None, "woocommerce.php": None}),
    ("plugins", "pod-simple-checkout", {"pod-simple-checkout.php": "Plugin Name: POD Simple Checkout", "readme.txt": None}),
)
ALLOWED_SUFFIXES = {".php", ".css", ".js", ".json", ".txt", ".png", ".jpg", ".jpeg", ".svg", ".webp", ".woff", ".woff2", ".mo", ".po", ".pot"}


def build(output: Path) -> None:
    output.mkdir(parents=True, exist_ok=True)
    for parent, slug, required in COMPONENTS:
        source = ROOT / parent / slug
        for name, marker in required.items():
            path = source / name
            if not path.is_file():
                raise ValueError(f"Missing required file: {path.relative_to(ROOT)}")
            if marker and marker not in path.read_text(encoding="utf-8")[:8192]:
                raise ValueError(f"Missing WordPress header: {path.relative_to(ROOT)}")

        files = []
        for path in sorted(source.rglob("*")):
            if path.is_symlink():
                raise ValueError(f"Symlinks are not allowed: {path}")
            relative = path.relative_to(source)
            if any(part.startswith(".") or part in {"node_modules", "vendor", "__pycache__"} for part in relative.parts):
                continue
            if path.is_file() and path.suffix.lower() in ALLOWED_SUFFIXES:
                files.append(path)

        archive = output / f"{slug}.zip"
        expected = {f"{slug}/{path.relative_to(source).as_posix()}" for path in files}
        with ZipFile(archive, "w", compression=ZIP_DEFLATED) as package:
            for path in files:
                entry = ZipInfo(f"{slug}/{path.relative_to(source).as_posix()}", (2020, 1, 1, 0, 0, 0))
                entry.compress_type = ZIP_DEFLATED
                entry.create_system = 3
                entry.external_attr = 0o100644 << 16
                package.writestr(entry, path.read_bytes())

        with ZipFile(archive) as package:
            if package.testzip() is not None or set(package.namelist()) != expected:
                raise ValueError(f"Archive validation failed: {archive}")
            for name in required:
                if f"{slug}/{name}" not in package.namelist():
                    raise ValueError(f"Required file absent from archive: {name}")
        print(f"Built {archive} ({len(files)} files)")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output-dir", type=Path, default=ROOT / "dist")
    build(parser.parse_args().output_dir.resolve())
