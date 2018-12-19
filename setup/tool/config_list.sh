#!/bin/sh -e

# Extract used configuration keys and associated default value from codebase.
# This utility relies on an approximate sed expression and will miss both
# complex expressions and multiple calls to `config` method in a single line.

(
	echo "KEY|DEFAULT VALUE"
	git --work-tree="$(dirname "$0")/../../" ls-files |
	grep -E '\.php$' |
	xargs sed -E -n "s/.*config *\\( *(\"([^\"]*)\"|'([^']*)')( *, *(\"[^\"]*\"|'[^']*'|[^)]*))?\\).*/\2\3|\5/p" |
	sort |
	uniq
) |
column -s '|' -t
