# Offline job geography

This directory contains an adapted geography reference database from the [Countries States Cities Database](https://github.com/dr5hn/countries-states-cities-database), downloaded on 2026-09-15. The database is distributed under ODbL 1.0; see LICENSE. Adaptations retain country, state and city names, identifiers, regions and population for disambiguation, and add common job-search aliases. This reference is separate from the application's job records.

Snapshot: 250 countries/territories, 5,308 subdivisions and 152,970 cities. Not every place has a current opening. Some upstream countries/territories have no state subdivisions.

## Rebuild

Download these files into this directory, then run `python scripts/build-job-geography.py` from the repository root:

- `countries-source.json`: https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/json/countries.json
- `states-source.json`: https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/json/states.json
- `cities-source.json.gz`: https://github.com/dr5hn/countries-states-cities-database/releases/latest/download/json-cities.json.gz

The builder creates a temporary database and replaces geography.sqlite when complete. Updated upstream exports can change counts. Retain the upstream license and attribution when distributing this adapted database. The downloaded source exports need not be deployed.

## Application behavior

- `/api/jobs/locations`: cascading options (`region`, `country`, `state`, `q`). City suggestions are limited to 100 prefix matches; users can continue typing to find any city.
- `/api/jobs`: filters `region`, `country`, `state`, `city` alongside existing job filters. Country accepts ISO alpha-2 codes, names and common aliases. State accepts the reference ID, name or subdivision code scoped to country.
- `JobGeography` derives locations from the published job's location text. Country metadata is a fallback. Multiple locations can be represented; locations without sufficient information remain unclassified.
- City/state context disambiguates repeated names. Otherwise a unique city or a major city's population is used; free-text feeds cannot guarantee complete geographic accuracy.
- The index uses the discovery cache version and refreshes when official imports invalidate it. There is no external geography API call at search time. PHP PDO SQLite is required.
- Aliases include Bangalore/Bengaluru, Bombay/Mumbai, Madras/Chennai, Gurgaon/Gurugram, Vancuover/Vancouver, Calfiornia/California and Andra Pradesh/Andhra Pradesh.
- Run `php artisan test --filter=JobGeographyTest` for isolated regression checks.