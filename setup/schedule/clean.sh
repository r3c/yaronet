#!/bin/sh -e

# Import configuration
token="$(dirname $0)"/.token

log_error()
{
	echo >&2 "$@"
}

log_info()
{
	test -n "$opt_quiet" || echo >&2 "$@"
}

prompt()
{
	local result

	stty -echo
	read -p "$1" result
	stty echo
	echo >&2

	echo "$result"
}

# Read command line arguments
opt_clean=
opt_quiet=
opt_token=

while getopts :chqtx opt; do
	case "$opt" in
		c)
			opt_clean=1
			;;

		h)
			log_error "$(basename $0) [-c] [-h] [-q] [-t] [-x] <base url>"
			log_error '  -c: trigger cleaning task on target site'
			log_error '  -h: display this help'
			log_error '  -q: quiet mode'
			log_error '  -t: create authentication token'
			log_error '  -x: debug mode'
			exit
			;;

		t)
			opt_token=1
			;;

		q)
			opt_quiet=1
			;;

		x)
			set -x
			;;

		:)
			log_error "missing argument for option -$OPTARG."
			exit 1
			;;

		*)
			log_error "unknown option -$OPTARG."
			exit 1
			;;
	esac
done

shift "$((OPTIND - 1))"

if [ $# -ne 1 ]; then
	log_error 'Missing base URL to website.'
	exit 1
fi

url="$1"

# Refresh authentication token
if [ -n "$opt_token" ]; then
	log_info 'Please provide administration account credentials to generate authentication token:'

	login="$(prompt 'login?')"
	password="$(prompt 'password?')"

	code="$(curl -c "$token" -d expire=8640000 -d login="$login" --data-urlencode "password=$password" -s -w '%{http_code}' "$url/users/signin")"

	if [ "$code" -eq 302 ]; then
		log_info 'Authentication OK.'
	else
		log_error 'Authentication failed.'
	fi
fi

# Clean data
if [ -n "$opt_clean" -a -r "$token" ]; then
	log_info 'Trigger cleaning task...'

	curl -s -b "$token" "$url/tasks/clean" |
	( ! grep -Fv 'Clean OK' )
fi
