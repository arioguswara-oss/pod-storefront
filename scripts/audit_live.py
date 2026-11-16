#!/usr/bin/env python3
"""GET-only public compatibility snapshot of GraphicTeeStore.com. No cookies or writes."""
import json
import re
from datetime import datetime, timezone
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlparse
from urllib.error import HTTPError
from urllib.request import Request, urlopen

ROOT = "https://graphicteestore.com"


class PublicPage(HTMLParser):
    def __init__(self):
        super().__init__()
        self.links = []
        self.theme_assets = set()
        self.generator = None
        self.variation_count = None
        self.variation_attributes = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "a":
            self.links.append(attrs.get("href", ""))
        if tag == "meta" and attrs.get("name") == "generator":
            self.generator = attrs.get("content")
        for key in ("href", "src"):
            match = re.search(r"/wp-content/themes/([^/]+)/", attrs.get(key, ""))
            if match:
                self.theme_assets.add(match.group(1))
        if tag == "form" and "variations_form" in attrs.get("class", ""):
            data = attrs.get("data-product_variations", "")
            if data and data != "false":
                variations = json.loads(data)
                self.variation_count = len(variations)
                self.variation_attributes = sorted({key for item in variations for key in item.get("attributes", {})})


def read(url):
    parsed = urlparse(url)
    if parsed.scheme != "https" or parsed.netloc != "graphicteestore.com" or parsed.query:
        raise ValueError("Only query-free public GraphicTeeStore.com URLs are allowed")
    request = Request(url, headers={"User-Agent": "PODStorefrontCompatibilityAudit/0.1.0 (GET-only)"})
    try:
        connection = urlopen(request, timeout=30)
    except HTTPError as error:
        connection = error
    with connection as response:
        final = urlparse(response.url)
        if final.netloc != "graphicteestore.com":
            raise ValueError("Unexpected redirect outside the testing website")
        page = PublicPage()
        page.feed(response.read().decode("utf-8", errors="replace"))
        return page, {"url": response.url, "http_status": response.status, "retry_after_seconds": response.headers.get('Retry-After'), "public_generator": page.generator, "theme_assets": sorted(page.theme_assets), "public_variation_count": page.variation_count, "variation_attributes": page.variation_attributes}


if __name__ == "__main__":
    home, first = read(ROOT + "/")
    report = {"observed_at_utc": datetime.now(timezone.utc).isoformat(), "method": "GET only; no login, cookie jar, cart mutation or order submission", "pages": [first]}
    if first['http_status'] == 200:
        for prefix in ("/product-category/", "/product/"):
            url = next((url for url in home.links if url.startswith(ROOT + prefix)), None)
            if url:
                _, page = read(url)
                report["pages"].append(page)
                if page['http_status'] != 200:
                    break
    else:
        report['note'] = 'Live inspection stopped. Honor Retry-After before running again; no automatic retry.'
    output = Path(__file__).resolve().parents[1] / "tests/results/public-live-audit.json"
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(json.dumps(report, indent=2) + "\n")
    print(json.dumps(report, indent=2))
