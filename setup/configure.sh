#!/bin/sh -e

module="$(realpath --relative-to "$(pwd)" "$(dirname "$0")/module")"
root="$(realpath --relative-to "$(pwd)" "$(dirname "$0")/../")"
setup="$(realpath --relative-to "$(pwd)" "$(dirname "$0")")"

# Check presence of executable
# $1: executable name
check()
{
	if ! which > /dev/null 2> /dev/null "$1"; then
		log 3 "Executable '$1' was not found, please install and add to \$PATH before deploying"

		return 1
	fi
}

# Duplicate source directory using either a copy ("duplicate" mode) or a link
# (either "symbolic" link on Unix or "junction" link on Windows).
# $1: link mode
# $2: source directory to which link will point to
# $3: path to target symbolic link
link()
{
	local base
	local mode="$1"
	local path
	local source="$module/$2"
	local target="$root/$3"

	if [ ! -d "$source" ]; then
		log 3 "Cannot create link to missing folder '$source'"

		return 1
	elif [ -h "$target" ]; then
		rm -f "$target"
	fi

	base="$(realpath --relative-to "$(pwd)" "$target")"

	case "$mode" in
		duplicate|'')
			if [ ! '(' -d "$target" ')' -a -e "$target" ]; then
				log 3 "Cannot create directory '$target', file already exists"

				return 1
			fi

			mkdir -p "$target"
			cp -r "$source/"* "$target/"

			;;

		junction)
			if [ -e "$target" ]; then
				log 3 "Cannot create junction link '$target', file already exists"

				return 1
			fi

			path="$(realpath --relative-to "$(pwd)" "$source")"

			if ! cmd /c mklink /J "$(echo "$base" | tr -s / '\\')" "$(echo "$path" | tr -s / '\\')" > /dev/null; then
				log 3 "Cannot create junction link to folder '$target'"

				return 1
			fi

			;;

		symbolic)
			if [ -e "$target" ]; then
				log 3 "Cannot create symbolic link '$target', file already exists"

				return 1
			fi

			path="$(realpath --relative-to "$(dirname "$base")" "$source")"

			if ! ln -s "$path" "$base"; then
				log 3 "Cannot create symbolic link to folder '$target'"

				return 1
			fi

			;;

		*)
			log 3 "Unknown directory link mode '$mode'"

			return 1

			;;
	esac

	log 0 "Linked folder '$target' to '$source'"
}

# Print log message to stderr
log()
{
	local prefix

	test "$1" -ge "$opt_verbosity" || return 0

	case "$1" in
		0)
			prefix="\033[1;36mDEBUG:"
			;;

		1)
			prefix="\033[1;37mINFO:"
			;;

		2)
			prefix="\033[1;33mWARN:"
			;;

		*)
			prefix="\033[1;31mERROR:"
			;;
	esac

	shift
	printf >&2 "${prefix} $@\033[0m\n"
}

# Read command line arguments
opt_deploy=
opt_link=
opt_verbosity=1

while getopts :d:hl:v opt; do
	case "$opt" in
		d)
			opt_deploy="$OPTARG"
			;;

		h)
			log 1 "yAronet configuration and deployment tool"
			log 1 "Usage: $(basename "$0") [-d <environment>] [-h] [-l <mode>] [-v]"
			log 1 "  -d <environment>: run deployment to given environment after setup"
			log 1 "  -h: display usage and exit"
			log 1 "  -l <mode>: link mode (duplicate, junction or symbolic)"
			log 1 "  -v: increase verbosity level"
			exit
			;;

		l)
			opt_link="$OPTARG"
			;;

		v)
			opt_verbosity=0
			;;

		:)
			log 3 "Missing argument for option '-$OPTARG'"
			exit 1
			;;

		*)
			log 3 "Unknown option '-$OPTARG'"
			exit 1
			;;
	esac
done

shift "$((OPTIND - 1))"

# Check mandatory executables
for executable in node npm; do
	check "$executable" || exit 1
done

# Install npm modules
(
	cd "$setup/module/deval" &&
	npm install --no-bin-links --silent ||
	log 2 "Could not execute npm install in $setup/module/deval, please retry manually."
)

# Link modules to library directory
link "$opt_link" amato/src src/library/amato
link "$opt_link" deval/src src/library/deval
link "$opt_link" glay/src src/library/glay
link "$opt_link" losp/src src/library/losp
link "$opt_link" queros/src src/library/queros
link "$opt_link" redmap/src src/library/redmap

if [ -n "$opt_deploy" ]; then
	# Check mandatory executables
	for executable in creep imagemin lessc node npm uglifyjs; do
		check "$executable" || exit 1
	done

	# Start deployment
	( cd "$root"/src && creep -y "$opt_deploy" )
fi
