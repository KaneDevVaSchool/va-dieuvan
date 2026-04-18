# -*- coding: utf-8 -*-
import re
from pathlib import Path
p = Path(__file__).resolve().parent / "resources/js/src/locales/vi.json"
t = p.read_text(encoding="utf-8")
fix = '        "links_only_detail": "Ch\u01b0a li\u00ean k\u1ebft phi\u1ebfu / chuy\u1ebfn \u2014 m\u1edf chi ti\u1ebft \u0111\u01a1n ph\u00eda tr\u00ean.",'
t2 = re.sub(r'        "links_only_detail": "[^"]*",', fix, t, count=1)
if t2 == t:
    raise SystemExit("replace failed")
p.write_text(t2, encoding="utf-8")
print("ok")
