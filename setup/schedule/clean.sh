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
	local caption="$2"
	local input="$1"
	local value

	if [ "$input" = hide ]; then
		stty -echo
		read -p "$caption" value
		stty echo
		echo >&2
	else
		read -p "$caption " value
	fi

	echo "$value"
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

	login="$(prompt show 'Administrator login?')"
	password="$(prompt hide 'Administrator password?')"

	code="$(curl -c "$token" -d expire=8640000 -d login="$login" --data-urlencode "password=$password" -o /dev/null -s -w '%{http_code}' "$url/users/signin")"

	if [ "$code" -eq 302 ]; then
		log_info 'Authentication OK, token created.'
	else
		log_error 'Authentication failed, please make sure login and password are valid.'
		exit 2
	fi
fi

# Clean data
if [ -n "$opt_clean" ]; then
	log_info 'Trigger cleaning task...'

	if [ -r "$token" ]; then
		curl -s -b "$token" "$url/tasks/clean" |
		( ! grep . )
	else
		log_error 'Missing authentication token, please run with "-t" option to create one.'
		exit 2
	fi
fi
