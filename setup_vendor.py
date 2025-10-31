# setup_vendor.py (versi robust + fallback)
import hashlib, os, sys, time
from pathlib import Path

try:
    import requests
    from requests.adapters import HTTPAdapter
    from urllib3.util.retry import Retry
except ImportError:
    print("Installing 'requests' package...")
    os.system(f"{sys.executable} -m pip install requests -q")
    import requests
    from requests.adapters import HTTPAdapter
    from urllib3.util.retry import Retry

ROOT = Path(__file__).resolve().parent
PUB  = ROOT / "public" / "vendor"

# Untuk setiap file, sediakan beberapa mirror (urutan = prioritas)
FILES = {
    # jQuery
    "jquery/jquery.min.js": [
        "https://code.jquery.com/jquery-3.6.4.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js",
        "https://unpkg.com/jquery@3.6.4/dist/jquery.min.js",
    ],

    # Bootstrap 5
    "bootstrap5/bootstrap.min.css": [
        "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css",
        "https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css",
        "https://unpkg.com/bootstrap@5.3.2/dist/css/bootstrap.min.css",
    ],
    "bootstrap5/bootstrap.bundle.min.js": [
        "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js",
        "https://unpkg.com/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js",
    ],

    # Bootstrap 4
    "bootstrap4/bootstrap.min.css": [
        "https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css",
        "https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/css/bootstrap.min.css",
        "https://unpkg.com/bootstrap@4.6.2/dist/css/bootstrap.min.css",
    ],
    "bootstrap4/bootstrap.bundle.min.js": [
        "https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js",
        "https://unpkg.com/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js",
    ],

    # DataTables 1.13.6
    "datatables/jquery.dataTables.min.js": [
        "https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/jquery.dataTables.min.js",
        "https://unpkg.com/datatables.net@1.13.6/js/jquery.dataTables.min.js",
    ],
    "datatables/dataTables.bootstrap5.min.js": [
        "https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs5/1.13.6/dataTables.bootstrap5.min.js",
        "https://unpkg.com/datatables.net-bs5@1.13.6/js/dataTables.bootstrap5.min.js",
    ],
    "datatables/dataTables.bootstrap5.min.css": [
        "https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css",
        "https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs5/1.13.6/dataTables.bootstrap5.min.css",
        "https://unpkg.com/datatables.net-bs5@1.13.6/css/dataTables.bootstrap5.min.css",
    ],
    "datatables/dataTables.bootstrap4.min.css": [
        "https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css",
        "https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs4/1.13.6/dataTables.bootstrap4.min.css",
        "https://unpkg.com/datatables.net-bs4@1.13.6/css/dataTables.bootstrap4.min.css",
    ],

    # Chart.js 4.4.1 (UMD)
    "chartjs/chart.umd.min.js": [
        "https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js",
        "https://unpkg.com/chart.js@4.4.1/dist/chart.umd.min.js",
    ],

    # SortableJS 1.15.2
    "sortable/Sortable.min.js": [
        "https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js",
        "https://unpkg.com/sortablejs@1.15.2/modular/sortable.esm.js",  # fallback ESM
    ],

    # Alpine.js 3.13.3 (CDN build)
    "alpine/alpine.min.js": [
        "https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js",
        "https://unpkg.com/alpinejs@3.13.3/dist/cdn.min.js",
    ],

    # Polyfills
    "polyfill/_promise.min.js": [
        "https://cdn.jsdelivr.net/npm/promise-polyfill@8.2.3/dist/polyfill.min.js",
        "https://cdnjs.cloudflare.com/ajax/libs/promise-polyfill/8.2.3/polyfill.min.js",
        "https://unpkg.com/promise-polyfill@8.2.3/dist/polyfill.min.js",
    ],
    "polyfill/_fetch.umd.js": [
        "https://cdn.jsdelivr.net/npm/whatwg-fetch@3.6.2/dist/fetch.umd.js",
        "https://cdnjs.cloudflare.com/ajax/libs/fetch/3.6.2/fetch.umd.js",
        "https://unpkg.com/whatwg-fetch@3.6.2/dist/fetch.umd.js",
    ],
}

BANNER = """/*!
 * Local vendor bundle for PTK Tracker — offline-friendly
 * Includes: jQuery, Bootstrap5/4, DataTables, Chart.js, Sortable, Alpine, Polyfills (Promise+fetch)
 */
"""

def sha1(data: bytes) -> str:
    import hashlib
    return hashlib.sha1(data).hexdigest()

def make_session():
    s = requests.Session()
    retries = Retry(
        total=5, connect=5, read=5, backoff_factor=0.6,
        status_forcelist=[429, 500, 502, 503, 504],
        allowed_methods=["GET", "HEAD"]
    )
    adapter = HTTPAdapter(max_retries=retries)
    s.mount("http://", adapter)
    s.mount("https://", adapter)
    s.headers.update({
        "User-Agent": "PTK-Tracker-setup/1.0 (+requests)",
        "Accept": "*/*",
    })
    return s

def dl_any(urls, dest: Path, session: requests.Session, timeout=60):
    dest.parent.mkdir(parents=True, exist_ok=True)
    last_err = None
    for i, url in enumerate(urls, 1):
        try:
            r = session.get(url, timeout=timeout, stream=True)
            r.raise_for_status()
            data = r.content
            dest.write_bytes(data)
            return url, len(data), sha1(data)
        except Exception as e:
            last_err = e
            print(f"   ⚠ mirror {i} failed: {e}")
            time.sleep(0.5)
    raise last_err

def main():
    print(f"➡  Target folder: {PUB}")
    PUB.mkdir(parents=True, exist_ok=True)
    s = make_session()

    # 1) Download all files (dengan fallback mirror)
    for rel, urls in FILES.items():
        dest = PUB / rel
        # skip jika sudah ada (hemat waktu)
        if dest.exists() and dest.stat().st_size > 0:
            print(f"↻  skip {rel} (exists)")
            continue
        print(f"↓  {rel}")
        try:
            used_url, size, digest = dl_any(urls, dest, s, timeout=90)
            print(f"   ✔ saved {size/1024:.1f} KB from {used_url} (sha1 {digest[:10]}...)")
        except Exception as e:
            print(f"   ✖ FAILED all mirrors: {e}")
            return 1

    # 2) Build single polyfill bundle (polyfill.min.js)
    try:
        promise = (PUB / "polyfill/_promise.min.js").read_text(encoding="utf-8", errors="ignore")
        fetch   = (PUB / "polyfill/_fetch.umd.js").read_text(encoding="utf-8", errors="ignore")
        bundle  = BANNER + "\n" + promise + "\n" + fetch + "\n"
        (PUB / "polyfill/polyfill.min.js").write_text(bundle, encoding="utf-8")
        # remove temp parts
        (PUB / "polyfill/_promise.min.js").unlink(missing_ok=True)
        (PUB / "polyfill/_fetch.umd.js").unlink(missing_ok=True)
    except Exception as e:
        print(f"   ✖ Polyfill bundle failed: {e}")
        return 1

    print("\n✅ DONE. Local libs installed under /public/vendor")
    print("   Use via {{ asset('vendor/...') }} in Blade (e.g. vendor/jquery/jquery.min.js)")
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
