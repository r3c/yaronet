yAronet TODO list
=================

TODO
----

### Archive

- [ ] delete unused glyphs in html/10: arrow-inside, chart, clock-red, crown, flag-blue, information, paper, table
- [ ] delete unused glyphs in html/16: bell, cut, ip, nuclear, zone-pencil

### Technical debt

- [ ] replace hard-coded SQL queries [sql-hardcode]
- [ ] remove hard-coded bbcode [markup-bbcode]
- [ ] remove hard-coded memory reclaim queries [sql-memory]
- [ ] fix missing SQL transactions [sql-transaction]
- [ ] split "edit" and "quote" features when creating new post
- [ ] inject only instance variables or constants in Deval templates [deval-inject]
- [x] fix favorites (and possibly other multi-value input fields) not accepting entries with a "," character

### Mobile UX

- [ ] stop using tables in template files [html-table]

### Post references

- [ ] find a way to prevent inconsistent references [dangling]
- [ ] find a clean way of preventing empty topics [empty-topic]

### Topic permissions

- [ ] remove hack for private topics [hack-private-topics]
- [ ] on topic edit, take permissions into account for parent section auto-complete

DONE
----

### Open source

- [x] refactor old search table [legacy-search]
- [x] remove SQL queries from pages [sql-page]
- [x] rename sp to emoji
- [x] remove hard-coded references to Boo account (728) [boo]
- [x] apply uniform coding style
- [x] rename and comment configuration file
- [x] make sync.sh portable or remove it
- [x] remove custom redirections .htaccess.dist and exclude from deployment
- [x] write INSTALL.md
- [x] write CONTRIBUTE.md
- [x] allow configuring website logo as well as mascot
- [x] use "duplicate" strategy by default in setup script
- [x] create install script to generate working configuration file
- [x] remove mascot (both file & dist configuration)
- [x] fix tests relying on "fr" locale
- [x] allow generating CSS from LESS without deploying
- [x] create administrator account from install.php
- [x] write README.md
- [x] remove partner and re-apply in separate commit
- [x] remove links to blogs & ynews & re-apply in separate commit
- [x] add copyright to footer
- [x] update "amato" module references to GitHub
- [x] update "glay" module references to GitHub
- [x] update "losp" module references to GitHub
- [x] update "queros" module references to GitHub
- [x] update "redmap" module references to GitHub

### Unclassified

- [x] escape html at rendering, not conversion
- [x] implement regexp scanner
- [x] switch everyone to Umen
- [x] create/find a routing library
- [x] remove cIDcheck
- [x] unify logs
- [x] move template <head> to common template
- [x] remove "load" handler (replace by "edit" handler)
- [x] create/find a DAO library
- [x] delete sujets2005 table
- [x] don't use column name as array key in joins
- [x] factorize field names and join conditions in SQL queries
- [x] replace all $mbI references by $user object
- [x] remove all references to $cook_id variable
- [x] use User object for login & all cookies
- [x] securize template names to avoid arbitration code execution
- [x] allow template override & move JSON responses in a separate template
- [x] add "fail" handler for jQuery requests
- [x] redirect /account/message.json => /account/message-1.json
- [x] lazy-load library files with "using" function
- [x] remplace mandatory "setup.sh" step for static resources
- [x] remove reference to $sql variable in HTML markup format
- [x] merge posts2003 and posts4004 into posts
- [x] convert old [source] tags to [code]
- [x] move "memos" table to "account_note"
- [x] associate one format per logger
- [x] add revision-dependent identifier to static URLs
- [x] create new "connect" entity & pages
- [x] mutualize post/echo page for all previews
- [x] fix duplicated users in presences
- [x] use one single global "time()" value
- [x] convert all posts to umen
- [x] fix the "\[img]\http://domain/image.png[/img]" reverse bug
- [x] migrate signatures & forum descriptions (htmlsign) to Umen
- [x] fix unrendered topic names in RSS pages
- [x] remove all "mysql_query" calls from yN code
- [x] fix invalid three-arguments db->get_first calls
- [x] always cancel magic quotes
- [x] use Umen for mini-messages
- [x] fix yn1664.error rendering
- [x] replace #&1234; unicode sequences by actual unicode characters
- [x] remove ids from posts
- [x] handle implicit relations (e.g. cache)
- [x] don't force relations to update entities
- [x] allow inner joins in remog
- [x] remove loose permissions
- [x] let client decide of joined entities
- [x] cleanup old search results
- [x] replace board.bookmark.peek by board.post.edit + JS HTML code generation
- [x] migrate login-based columns
- [x] merge relations & external join values
- [x] shift post position by one in ref (./) tags & display
- [x] find a replacement for headers hack in index/json templates
- [x] split post "create", "edit" and "quote" processes
- [x] replace sujetslasts by index on topic update time
- [x] check cache when accessing entity via another one (e.g. topics via bookmarks)
- [x] merge Bookmark::track & Bookmark::watch
- [x] fix redirect location of "track" endpoint
- [x] solve "primary vs key" mix in base model class
- [x] support "where not exists" in Queros to fix dposts2
- [x] per-link popup size
- [x] create alternative to "favorites" popup
- [x] fix links to "search" page from yn1624 template
- [x] check user flag "FLAG_DISABLE" in pages
- [x] open minimsg popup on nick click
- [x] fix multipost issue
- [x] missing activation link on new pages
- [x] don't allow posts for non confirmed users
- [x] remove support for old "copy" format
- [x] fix 401 issue for bookmark/ln
- [x] keyboard navigation shortcuts
- [x] use forum template in new board pages
- [x] add page for board.forum.view
- [x] use icoT/entete from parent forum
- [x] handle alerts in JSON endpoints
- [x] preserve forum id in links to external sections
- [x] remove caller-specified relations
- [x] improve main menu in reduced mode
- [x] use local storage for "citations" rather than a SQL table
- [x] fix JS quote HTML decoding
- [x] host avatars
- [x] remove hacks for "smileys perso"
- [x] fix stats page (bars not aligned in some browsers)
- [x] host forum icons
- [x] use same behavior for account/memo and help/page: silently (in entity code) delete empty memo
- [x] use forum alias for all links to board.forum.view
- [x] always search in "alias" for forums, "id"
- [x] pass $query and $router as a single parameter
- [x] replace "create_member" tests by MODERATE permission on topic edit
- [x] move board_topic.create_member & board_topic.create_time to board_topic_cache
- [x] replace flags by separate is_X fields
- [x] allow value/label pairs in 'select' type like for 'radio' type in control-form
- [x] move post state filtering from templates to pages [post-state-page]
- [x] merge JS "peek" code in yn-board-bookmark.js and umen.js
- [x] allow aliases in language files
- [x] remove temporary code for language override [language-default]
- [x] replace ?version query string by /version/ path for static resources
- [x] protect SQL queries executed in "for" loops depending on user-injected arrays
- [x] sanitize SQL queries parameters input in entity/* classes
- [x] use Model for Account\Message entity
- [x] explode entity export method into "export()" (raw) and "render(format)" (recursive)
- [x] simplify "peek" test in topic-view page [topic-peek]
- [x] improve pvg & magnetite themes on narrow screens
- [x] remove inline JavaScript calls [inline-js]
- [x] replace {cut} losp function by ellipsis CSS
- [x] fix members page (can't show all members when 100+ share the same first letter)
- [x] remove need to reload cache for just created entities [reload-cache]
- [x] implement admin logs
- [x] cleanup $pages & $page_X variables in HTML templates using control-page
- [x] allow localization of strings used in code [locale-code]
- [x] remove hack for invisible topics [inv-topic]
- [x] remove invalid Umen dependency [umen-dependency]
- [x] rename "board_member" to "board_profile"
- [x] use email library [email]
- [x] adding missing foreign forum information in topic view [forum-null]
- [x] remove json/yn-account-memo-view.template
- [x] do not throw exception when template file is not found
- [x] replace 127.0.0.1 / ::1 by private IP check [private-ip]
- [x] allow "self" not to be in first position in activities [activity-self]
- [x] refactor mini-messages feature [legacy-mmsg]
- [x] replace library-umen-ref and json replacements by frame
- [x] remove duplicated template code in board.topic.view and board.post.search
- [x] create "shout" entity for flashchat messages
- [x] limit login length on user edit
- [x] missing forum description breaks home page layout
- [x] add help message when creating new forum
- [x] remove "gallery" section access
- [x] use object references for "*.value" strings (except formatted-one, need alias reassignment)
- [x] remove hack to support ignore option [hack-ignore]
- [x] remove global references to logger [global-logger]
- [x] make "items" property consistent across flags, image, radio and select control types
- [x] use html wrapper whenever possible in Devate templates
- [x] remove global references to user [global-user]
- [x] remove global references to sql [global-sql]
- [x] isolate blogs [blog]
- [x] replace legacy.poll
- [x] replace "stats" system [legacy-stats]
- [x] delete legacy.metric
- [x] replace backend code by full JS for JS quotes [js-quote]
- [x] replace legacy.alert
- [x] implement account.user.search page and provide search by ordered by pulse_time
- [x] replace legacy.access.profile by board.section.permission
- [x] replace legacy.access.admin by board.forum.permission
- [x] remove redirection code for legacy pages [legacy-page]
- [x] replace legacy.sp
- [x] move common string aliases to dedicated xml file
- [x] use different time windows for "too many requests" safety mechanism
- [x] replace legacy.ban
- [x] factorize image receive code [image-receive]
- [x] merge ".create" and ".edit" for log event strings
- [x] unify entity-<id> vs entity/<id> in URLs
- [x] fix draft not reset on post from bookmarks
- [x] unify suffix with "*" mandatory field labels
- [x] replace "viewforum" system [legacy-viewforum]
- [x] unify ul.links in panel-header vs anchors in panel-footer
- [x] use consistent naming scheme for Deval variables
- [x] show "search" link near "account", "chat" and "disconnect" ones
- [x] use a single "wrap" statement and local "unwrap" blocks when supported by Deval
- [x] move search table to main database [sql-database-alt]
- [x] do not reference MessageBox entity outside from entity/account/message.php
