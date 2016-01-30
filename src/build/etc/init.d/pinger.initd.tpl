#!/bin/bash
#
# Pinger service.
#
# Copyright (C)  2016 Sergey D. Ivnaov
#

# chkconfig: 345 35 65
# description: Pinger service.
#
### BEGIN INIT INFO
# Provides:       pinger
# Default-Start:  2 3 4 5
# Default-Stop:   0 1 6
# Description:    Pinger service.
### END INIT INFO

PATH=$PATH:/bin:/sbin:/usr/sbin:@base@@sbindir@

################################################################################

[ -r @base@/etc/default/pinger ] && . @base@/etc/default/pinger

if [ -z "${DEBUG}" ] || [ 0 -eq ${DEBUG} ] ; then
    DEBUG=0;
fi

if [ -z "${ENABLED}" ] || [ 0 -eq ${ENABLED} ] ; then
    >&2 printf "\nPinger service is not enabled.\n";
    >&2 printf "  Add 'ENABLED=\"1\"' to @base@/etc/default/pinger\n";
    exit 1;
fi

################################################################################

case "$1" in
start|stop|restart|reload|status|help|*)
    @base@@sbindir@/pinger $1;
    ;;
install|uninstall)
    >&2 echo "\nNot implementer yet.\n";
    exit 1;
esac

################################################################################

echo;

exit ${RETVAL};

# eof