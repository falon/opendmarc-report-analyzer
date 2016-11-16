# opendmarc-report-analyzer
A bunch of statistics from your OpenDMARC DB

## Require
Trusted Domain Project OpenDMARC milter with MySQL DB accessible from host where you install this program.
## Install
- Install via composer
```
    "require": {
        "php": ">=5.5",
        "falon/opendmarc-report-analyzer"
    }
```
- Move style.css and ajaxsbmt.js in DOCUMENT_ROOT/include dir if you haven't already from my others projects.
- Move dmarc.conf-default in dmarc.conf.
- Edit dmarc.conf with DB info from OpenDMARC
