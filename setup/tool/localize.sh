#!/bin/sh -e

input_base=http://localhost
input_directory="$(dirname "$0")/../../static"
input_static=/yaronet/static
input_www=/yaronet/src

output_directory="$(dirname "$0")/output"
output_static=static

duplicate()
{
	local relative="$1"
	local destination="$3"
	local source="$2"

	mkdir -p "$(dirname "$destination/$relative")"
	cp "$source/$relative" "$destination/$relative"
}

localize()
{
	local expression
	local html="$(mktemp)"
	local name="$2"
	local pattern="([\"'])$input_static/([^\"']*)([\"'])"
	local source
	local url="$1"

	mkdir -p "$path/$output_static"

	# Save page
	curl -s "$input_base$input_www/$url" > "$html"

	# Copy referenced static resources
	sed -r "s@$pattern@\\1$input_static/\\2\\3\n@g" "$html" |
	sed -nr "s@.*$pattern.*@\\2@p" |
	while read source; do
		case "$source" in
			*.js)
				localize_js "$source"
				;;

			*.less)
				localize_less "$source" "$(dirname "$source")"
				;;

			*)
				duplicate "$source" "$input_directory" "$output_directory/$output_static"
				;;
		esac
	done

	# Rewrite static URLs & highlight first post
	read -r -d '' expression << EOF || true
s@$pattern@\\1$output_static/\\2\\3@g
s@([\"'])$input_www/([^\"']*)([\"'])@about:blank@g
s@(class)=\"(panel post[^\"]*)\"@\\1=\"\\2 panel-hl\"@
EOF

	sed -r "$expression" "$html" > "$output_directory/$name.html"

	rm "$html"
}

localize_js()
{
	local path
	local source="$1"

	path="$output_directory/$output_static/$source"

	# Minify source JavaScript into target path
	mkdir -p "$(dirname "$path")"
	uglifyjs --compress --mangle -- "$input_directory/$source" > "$path"
}

localize_less()
{
	local base="$2"
	local parent
	local source="$1"

	# Recursively process and copy included Less files
	parent="$(dirname "$source")"

	sed -nr "s/^@import '([^']*)'.*/\1/p" "$input_directory/$source" |
	while read import; do
		localize_less "$parent/$import" "$base"
	done

	# Copy files referenced through url() operator
	sed -nr "s@.*url\([\"']?([^\"'()?#]*)([?#][^\"'()]*)?[\"']?\).*@\1@p" "$input_directory/$source" |
	while read url; do
		duplicate "$base/$url" "$input_directory" "$output_directory/$output_static"
	done

	# Copy current less file
	duplicate "$source" "$input_directory" "$output_directory/$output_static"
}

for theme in amethyst chlorite citrine obsidian; do
	mkdir -p "$output_directory/sample"
	cp "$input_directory/layout/html/theme/$theme/color.inc.less" "$output_directory/sample/$theme.color.inc.less"
done

localize 'bookmarks' bookmarks
localize 'forums/10-site' forum
localize '' home
localize 'topics/150713-mises-jour-de-yaronet/3' topic

cat << EOF > "$output_directory/README"
How to test your new theme:

- Open file ./$output_static/layout/html/theme/kyanite/color.inc.less
- Edit any of the Less.js variables (@something) defined there
- Open (or refresh) any of the ./*.html file to preview your changes
- Overrite color file with one from ./sample/*.less to use a different base
- Try not to change any other *.less file to minimize delta between themes

And don't forget to share your piece of art when done :)
EOF
