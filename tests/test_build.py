"""Packaging checks: reproducibility, independent roots, metadata and unsafe input."""
import importlib.util
import json
import shutil
import tempfile
import unittest
from pathlib import Path
from zipfile import ZipFile

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("pod_build", ROOT / "scripts/build.py")
build_module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(build_module)


class PackageTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name) / "source"
        self.root.mkdir()
        for folder in ("themes", "plugins"):
            shutil.copytree(ROOT / folder, self.root / folder)
        shutil.copy(ROOT / "releases.json", self.root / "releases.json")
        self.output = Path(self.temp.name) / "output"

    def test_independent_versioned_and_reproducible_packages(self):
        first = build_module.build(self.output, root=self.root)
        second = build_module.build(self.output, root=self.root)
        self.assertEqual(first, second)
        self.assertEqual(len(first), 2)
        for entry in first:
            with ZipFile(self.output / entry["file"]) as archive:
                slug = entry["file"].removesuffix(f'-{entry["version"]}.zip')
                self.assertTrue(all(name.startswith(slug + "/") for name in archive.namelist()))
                self.assertFalse(any(name.endswith(".md") or "/." in name for name in archive.namelist()))
                self.assertIsNone(archive.testzip())
        self.assertEqual(json.loads((self.output / "manifest.json").read_text()), first)

    def test_version_mismatch_rejected(self):
        path = self.root / "themes/pod-store-theme/style.css"
        path.write_text(path.read_text().replace("Version: 0.1.0", "Version: 9.9.9"))
        with self.assertRaisesRegex(ValueError, "version mismatch"):
            build_module.build(self.output, root=self.root)

    def test_missing_template_rejected(self):
        (self.root / "themes/pod-store-theme/front-page.php").unlink()
        with self.assertRaisesRegex(ValueError, "required file"):
            build_module.build(self.output, root=self.root)

    def test_symlinks_and_output_in_component_rejected(self):
        source = self.root / "themes/pod-store-theme"
        link = source / "leak.txt"
        link.symlink_to(self.root / "releases.json")
        with self.assertRaisesRegex(ValueError, "Symlinks"):
            build_module.build(self.output, root=self.root)
        link.unlink()
        with self.assertRaisesRegex(ValueError, "inside a component"):
            build_module.build(source / "build", root=self.root)

    def test_theme_has_no_store_migrations_or_writes(self):
        # Guard this presentation-only release against accidental write hooks.
        forbidden = ("update_option(", "update_post_meta(", "wp_insert_post(", "wp_update_post(", "flush_rewrite_rules(", "wc_create_order(", "$wpdb->", "woocommerce_payment_gateways", "register_activation_hook(")
        for php in (self.root / "themes").rglob("*.php"):
            compact = php.read_text().replace(" ", "").replace("\t", "").replace("\n", "")
            for token in forbidden:
                self.assertNotIn(token, compact, f"Store write API in {php.name}")


if __name__ == "__main__":
    unittest.main()
