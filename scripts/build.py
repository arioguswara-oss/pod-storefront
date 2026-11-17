#!/usr/bin/env python3
"""Build deterministic, versioned WordPress packages; no deployment or network calls."""
import argparse
import hashlib
import json
import re
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile, ZipInfo

ROOT = Path(__file__).resolve().parents[1]
COMPONENTS = {
    "theme": ("themes", "pod-store-theme", {
        "style.css": "Theme Name: POD Store Theme",
        "index.php": None, "functions.php": None, "header.php": None,
        "footer.php": None, "front-page.php": None, "page.php": None,
        "inc/woocommerce.php": None, "assets/js/navigation.js": None,
    }),
    "plugin": ("plugins", "pod-simple-checkout", {
        "pod-simple-checkout.php": "Plugin Name: POD Simple Checkout",
        "readme.txt": None,
        "includes/class-cart.php": None, "includes/class-plugin.php": None,
        "includes/class-shopping-ux.php": None, "assets/shopping.js": None, "assets/shopping.css": None,
        "includes/class-settings.php": None, "includes/class-checkout.php": None,
        "assets/product.js": None, "assets/product.css": None, "assets/checkout.css": None,
    }),
}
ALLOWED_SUFFIXES = {".php", ".css", ".js", ".json", ".txt", ".png", ".jpg", ".jpeg", ".svg", ".webp", ".woff", ".woff2", ".mo", ".po", ".pot"}


def build(output: Path, component: str = "all", root: Path = ROOT) -> list:
    releases = json.loads((root / "releases.json").read_text())
    prepared = []
    for kind, (parent, slug, required) in COMPONENTS.items():
        if component != "all" and component != kind:
            continue
        release = releases[kind]
        version = release["version"]
        if release["slug"] != slug or not re.fullmatch(r"\d+\.\d+\.\d+(?:-[a-z0-9.-]+)?", version):
            raise ValueError(f"Invalid release metadata for {slug}")
        source = root / parent / slug
        if source.is_symlink():
            raise ValueError(f"Symlink component: {slug}")
        for name, marker in required.items():
            path = source / name
            if not path.is_file() or path.is_symlink():
                raise ValueError(f"Missing or unsafe required file: {slug}/{name}")
            if marker:
                text = path.read_text(encoding="utf-8")[:8192]
                if marker not in text or not re.search(r"^[ \t*]*Version:\s*" + re.escape(version) + r"\s*$", text, re.M):
                    raise ValueError(f"WordPress header/version mismatch: {slug}/{name}")
        files = []
        for path in sorted(source.rglob("*")):
            if path.is_symlink():
                raise ValueError(f"Symlinks are not allowed: {path}")
            relative = path.relative_to(source)
            if any(part.startswith(".") or part in {"node_modules", "vendor", "__pycache__"} for part in relative.parts):
                continue
            if path.is_file() and path.suffix.lower() in ALLOWED_SUFFIXES:
                files.append(path)
        archive_name = release.get("archive_name", f"{slug}-{version}.zip")
        if archive_name not in {f"{slug}-{version}.zip", f"{slug}-v{version}.zip"}:
            raise ValueError(f"Invalid archive name for {slug}")
        prepared.append((kind, slug, version, release["status"], source, files, required, archive_name))

    output = output.resolve()
    for _, _, _, _, source, _, _, _ in prepared:
        if output == source.resolve() or source.resolve() in output.parents:
            raise ValueError("Output directory cannot be inside a component")
    output.mkdir(parents=True, exist_ok=True)
    manifest = []
    for kind, slug, version, status, source, files, required, archive_name in prepared:
        archive = output / archive_name
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
            if any(f"{slug}/{name}" not in package.namelist() for name in required):
                raise ValueError(f"Required file absent from {archive}")
        digest = hashlib.sha256(archive.read_bytes()).hexdigest()
        manifest.append({"component": kind, "file": archive.name, "version": version, "status": status, "sha256": digest, "files": len(files)})
        print(f"Built {archive} ({len(files)} files; {status})")
    (output / "manifest.json").write_text(json.dumps(manifest, indent=2) + "\n")
    (output / "SHA256SUMS").write_text("".join(f"{entry['sha256']}  {entry['file']}\n" for entry in manifest))
    return manifest


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output-dir", type=Path, default=ROOT / "dist")
    parser.add_argument("--component", choices=("all", "theme", "plugin"), default="all")
    args = parser.parse_args()
    build(args.output_dir, args.component)
