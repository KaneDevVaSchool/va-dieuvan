import json
from pathlib import Path

root = Path(__file__).resolve().parents[1]
for name in ("vi", "en"):
    p = root / "resources/js/src/locales" / f"{name}.json"
    d = json.loads(p.read_text(encoding="utf-8"))
    cp = d.setdefault("costs_page", {})
    if name == "vi":
        cp.setdefault("section_filters", "Bộ lọc")
        cp.setdefault("per_page_unit", "dòng")
    else:
        cp.setdefault("section_filters", "Filters")
        cp.setdefault("per_page_unit", "rows")
    p.write_text(json.dumps(d, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
print("ok")
