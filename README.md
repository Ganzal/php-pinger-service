Pinger
======

Couple of projects for monitoring host availability with `ping` command.

Written for fun for production purposes.

Enjoy.


php-pinger-service
------------------

Simple-functionality PHP Daemon for checking host availability.

Native front-end located in [lua-nginx-pinger-frontend](https://github.com/ganzal/lua-nginx-pinger-frontend) project.


### Components


#### pinger-console

Command-line utility for managing hosts database.

Can be used by root or by regular user which is member of group `pinger`.


##### pinger-console usage

`pingerc {add|rem|enable|disable|list|help}`

* `add LABEL FQDN` - add host
* `rem LABEL` - remove host
* `enable LABEL` - enable host
* `disable LABEL` - disable hosts
* `list` - list hosts and statuses
* `help` - show help text


#### pinger-service

Daemon that doing `ping` of hosts.


##### pinger-service usage

`service pinger {start|stop|restart|reload|status|help}`

* `start` - launch daemon
* `stop`  - kill daemon
* `restart` - kill daemon and then launch it back <sup>[1]</sup>
* `reload` - reload configuration without restart<sup>[2]</sup>
* `status` - show daemon status and PID if its currently running
* `help` - show help text

<sup>**[1]**</sup> Currently bugged.<br>
<sup>**[2]**</sup> Not implemented yet.

### Requirements

1. [php](http://php.net/) (version `5.5.x` or `5.6.x`) with CLI SAPI and `mysqli`, `posix` and `pcntl` modules<br>
**Note:** `pcntl_*` functions must not be listed in `disable_functions`
2. [MariaDB](http://mariadb.org/) or [MySQL](http://mysql.com/) server
3. [Redis](https://redis.io/) server
4. [ant](http://ant.apache.org/) for building from source

Tested on **Ubuntu Linux 14.04 LTS** so **Linux**-based OS and **Debian/Ubuntu** distro are recommended.

### Installation

1. exec `ant install` from source folder
2. edit configuration (default location: `/usr/local/etc/pinger-config.php`)
3. create database
4. create tables (eg. using `create_tables.sql`)
5. start service via `service pinger start`
6. *[optionaly]* append regular users to group `pinger` (eg. `usermod -aG pinger login`) to grant them permissions to launch `pingerc` (pinger-console)


### TODO

* Human-friendly interface (*Maybe*)
* Install/Uninstall scripts and helpers (*Even if I think that instructions listed above is just enough to success*)
* Debian/Ubuntu packages (*Why not?*)
* Unit-tests (*Why not?*)
* Fix known bugs


## License

MIT License: see the [LICENSE](LICENSE) file.

*eof*
