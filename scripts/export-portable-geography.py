"""Export the licensed SQLite reference to PHP-driver-independent JSON."""
import hashlib
import json
import sqlite3
from pathlib import Path
root = Path(__file__).resolve().parents[1] / "resources/data/job-geography"
db = sqlite3.connect(root / "geography.sqlite")
out = root / "portable"
def save(path, data):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=True, separators=(",", ":")), encoding="utf-8")
db.row_factory = sqlite3.Row
for table in ["countries", "states"]:
    save(out / (table + ".json"), [dict(r) for r in db.execute("SELECT * FROM " + table)])
db.row_factory = None
shards = {format(i, "02x"): {} for i in range(256)}
aliases = {}
for term, country, state, city, kind, population in db.execute("SELECT term,country,state,city,kind,population FROM places"):
    bucket = hashlib.sha1(term.encode()).hexdigest()[:2]
    shards[bucket].setdefault(term, []).append([country, state, city, kind, population])
    if kind == "city":
        aliases.setdefault(country, {}).setdefault(city, []).append(term)
for bucket, rows in shards.items():
    save(out / "places" / (bucket + ".json"), rows)
countries = {}
for country, state, name, term in db.execute("SELECT country,state,name,term FROM cities"):
    countries.setdefault(country, []).append([state, name, term, aliases.get(country, {}).get(name, [])])
for country, rows in countries.items():
    save(out / "cities" / (country + ".json"), rows)
db.close()
print("Exported portable geography reference.")
