%global systemd (0%{?fedora} >= 18) || (0%{?rhel} >= 7)

Summary: A complete, more than an RBL Management System.
Name: opendmarc-report-analyzer
Version: 1.0.0
Release: 0%{?dist}
Group: Networking/Mail
License: Apache-2.0
URL: https://falon.github.io/%{name}/
Source0: https://github.com/falon/%{name}/archive/master.zip
BuildArch:	noarch

# Required for all versions
Requires: httpd >= 2.4.6
Requires: mod_ssl >= 2.4.6
Requires: php >= 7.1
Requires: FalonCommon >= 0.1.1


%if %systemd
# Required for systemd
%{?systemd_requires}
BuildRequires: systemd
%endif

%description
%{name} 
provides a web interface to query the OpenDMARC MySQL DB.
You can extract many info about blocked or passed domains,
and why. Some statistics help you to see how domains
authenticate and align.
GIT: https://github.com/falon/opendmarc-report-analyzer

%clean
rm -rf %{buildroot}/

%prep
%autosetup -n %{name}-master


%install
# Web HTTPD conf
install -D -m0444 contrib/%{name}.conf-default %{buildroot}%{_sysconfdir}/httpd/conf.d/%{name}.conf
sed -i 's|\/var\/www\/html\/%{name}|%{_datadir}/%{name}|' %{buildroot}%{_sysconfdir}/httpd/conf.d/%{name}.conf

# opendmarc-report-analyzer files
mkdir -p %{buildroot}%{_datadir}/%{name}
cp -a * %{buildroot}%{_datadir}/%{name}/
mv %{buildroot}%{_datadir}/%{name}/%{name}.conf-default %{buildroot}%{_sysconfdir}/%{name}.conf
sed -i 's|%{name}.conf|%{_sysconfdir}/%{name}.conf|' %{buildroot}%{_datadir}/%{name}/*.php
## Remove unnecessary files
rm %{buildroot}%{_datadir}/%{name}/_config.yml
rm -rf %{buildroot}%{_datadir}/%{name}/contrib

##File list
find %{buildroot}%{_datadir}/%{name} -mindepth 1 -type f -print0 | xargs -0 -L1 | grep -v \.conf$ | grep -v \.git | grep -v %{name}/LICENSE | grep -v %{name}/README\.md | sed -e "s@$RPM_BUILD_ROOT@\"@" | sed "s/$/\"/" > FILELIST


%post
case "$1" in
  2)
	echo -en "\n\n\e[33mRemember to check any change in %{_sysconfdir}/%{name}.conf.\e[39m\n\n"
  ;;
esac

%files -f FILELIST
%license %{_datadir}/%{name}/LICENSE
%doc %{_datadir}/%{name}/README.md
%config(noreplace) %{_sysconfdir}/%{name}.conf
%config(noreplace) %{_sysconfdir}/httpd/conf.d/%{name}.conf

%changelog
* Wed Jul 31 2019 Marco Favero <marco.favero@csi.it> 1.0.0-0
- First build
