# opendmarc-report-analyzer
A bunch of statistics from your OpenDMARC DB

## Require
Trusted Domain Project OpenDMARC milter with MySQL DB accessible from host where you install this program.
## Install
### By source
- PHP 5.4 and 7 tested
- Install the [Falon-Common](https://github.com/falon/falon-common) shared web library
- Move opendmarc-report-analyzer.conf-default in opendmarc-report-analyzer.conf.
- Edit "opendmarc-report-analyzer.conf" with DB info from OpenDMARC

### By RPM
- Create a file `/etc/yum.repos.d/falon.repo` with
```
[falon]
name=Falon Repo
baseurl=https://yum.fury.io/csi/
enabled=1
gpgcheck=0
```

- yum install opendmarc-report-analyzer
- systemctl reload httpd.service

There are no requisite about OpenDMARC and MySQL. You can install these tools on other hosts.
