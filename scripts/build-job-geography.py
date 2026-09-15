"""Build the offline ODbL geography snapshot from upstream JSON exports."""
import gzip, json, re, sqlite3, unicodedata, tempfile
from pathlib import Path
root = Path(__file__).resolve().parents[1] / 'resources/data/job-geography'
def key(s):
    return re.sub(r'[^a-z0-9]+', ' ', unicodedata.normalize('NFKD', s).encode('ascii','ignore').decode().lower()).strip()
countries=json.loads((root/'countries-source.json').read_text(encoding='utf-8-sig'))
states=json.loads((root/'states-source.json').read_text(encoding='utf-8-sig'))
cities=json.load(gzip.open(root/'cities-source.json.gz','rt',encoding='utf-8'))
handle=tempfile.NamedTemporaryFile(dir=root,suffix='.sqlite',delete=False); temporary=Path(handle.name); handle.close()
db=sqlite3.connect(temporary)
db.executescript('CREATE TABLE countries(code TEXT PRIMARY KEY,name TEXT,region TEXT); CREATE TABLE states(id TEXT PRIMARY KEY,country TEXT,name TEXT,code TEXT); CREATE TABLE places(term TEXT,country TEXT,state TEXT,city TEXT,kind TEXT,population INTEGER); CREATE TABLE cities(country TEXT,state TEXT,name TEXT,term TEXT);')
def place(term,country,state='',city='',kind='country',population=0):
    db.execute('INSERT INTO places VALUES(?,?,?,?,?,?)',(key(term),country,str(state),city,kind,population or 0))
for c in countries:
    db.execute('INSERT INTO countries VALUES(?,?,?)',(c['iso2'],c['name'],c['region'] or 'Other'))
    place(c['name'],c['iso2']);place(c['iso3'],c['iso2'])
for s in states:
    db.execute('INSERT INTO states VALUES(?,?,?,?)',(str(s['id']),s['country_code'],s['name'],s['iso2']))
    place(s['name'],s['country_code'],s['id'],kind='state')
for c in cities:
    db.execute('INSERT INTO cities VALUES(?,?,?,?)',(c['country_code'],str(c['state_id']),c['name'],key(c['name'])))
    place(c['name'],c['country_code'],c['state_id'],c['name'],'city',c.get('population'))
for code,aliases in {'US':['USA','US','United States of America','America','U.S.','U.S.A.'],'GB':['UK','United Kingdom','Great Britain','England','Scotland','Wales'],'AE':['UAE','U.A.E.'],'KR':['South Korea'],'CZ':['Czech Republic']}.items():
    for alias in aliases: place(alias,code)
for canonical,aliases in {'New Delhi':['Delhi'],'Bengaluru':['Bangalore','Bangaluru'],'Mumbai':['Bombay'],'Chennai':['Madras'],'Gurugram':['Gurgaon'],'Vancouver':['Vancuover'],'California':['Calfiornia'],'Andhra Pradesh':['Andra Pradesh','Andra Parsesh']}.items():
    rows=db.execute('SELECT country,state,city,kind,population FROM places WHERE term=?',(key(canonical),)).fetchall()
    if not rows and canonical=='Bengaluru': rows=db.execute("SELECT country,state,city,kind,population FROM places WHERE term='bangalore' AND country='IN'").fetchall()
    for row in rows:
        for alias in aliases+[canonical]: place(alias,*row)
db.executescript('CREATE INDEX places_term ON places(term); CREATE INDEX cities_parent ON cities(country,state); CREATE INDEX cities_term ON cities(term); CREATE INDEX states_country ON states(country);')
db.commit(); db.execute('VACUUM'); db.close(); temporary.replace(root/'geography.sqlite')
print(f'Built {len(countries)} countries, {len(states)} states, {len(cities)} cities')