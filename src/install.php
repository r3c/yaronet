<?php

define('YARONET', 'install');

function make_block_advice($sections)
{
    return '<blockquote>' . implode('', $sections) . '</blockquote>';
}

function make_block_form($submit, $sections)
{
    return
        '<blockquote>' .
            '<form action="" method="POST">' .
                implode('', $sections) .
                (
                    $submit ?
                    '<div class="section">' .
                        '<input type="submit" value="Submit" />' .
                    '</div>' :
                    ''
                ) .
            '</form>' .
        '</blockquote>';
}

function make_block_status($status, $sections)
{
    return '<blockquote class="section status-' . $status . '">' . implode('', $sections) . '</blockquote>';
}

function make_section_config($title, $body, $templates)
{
    return
        '<div class="section">' .
            '<h2>' . $title . '</h2>' .
            '<p>' . $body . '</p>' .
            implode('', array_map(function ($name, $contents) {
                return
                    '<p>Save following contents as `' . make_literal($name) . '`:</p>' .
                    '<textarea rows="8">' . make_literal($contents) . '</textarea>';
            }, array_keys($templates), $templates)) .
        '</div>';
}

function make_section_form($title, $fields)
{
    return
        '<div class="section">' .
            '<h2>' . $title . '</h2>' .
            implode('', array_map(function ($field) {
                $name = $field['name'];

                if (!isset($field['caption'])) {
                    return '<input name="' . make_literal($name) . '" type="hidden" value="' . make_literal($field['text']) . '" />';
                } elseif (isset($field['options'])) {
                    $current = isset($_POST[$name]) ? (string)$_POST[$name] : '';
                    $input = '<select class="input" name="' . make_literal($name) . '">';

                    foreach ($field['options'] as $value => $caption) {
                        $input .= '<option value="' . make_literal($value) . '"' . ($current === (string)$value ? ' selected' : '') . '>' . make_literal($caption) . '</option>';
                    }

                    $input .= '</select>';
                } elseif (isset($field['password'])) {
                    $value = isset($_POST[$name]) ? $_POST[$name] : $field['password'];
                    $input = '<input class="input" name="' . make_literal($name) . '" type="password" value="' . make_literal($value) . '" />';
                } elseif (isset($field['text'])) {
                    $value = isset($_POST[$name]) ? $_POST[$name] : $field['text'];
                    $input = '<input class="input" name="' . make_literal($name) . '" type="text" value="' . make_literal($value) . '" />';
                } else {
                    $input = '';
                }

                return
                    '<div class="field">' .
                        '<span class="caption">' . $field['caption'] . '</span>' .
                        $input .
                        '<span class="help">' . $field['help'] . '</span>' .
                    '</div>';
            }, $fields)) .
        '</div>';
}

function make_section_text($title, $sections)
{
    return
        '<div class="section">' .
            '<h2>' . $title . '</h2>' .
            implode('', array_map(function ($section) {
                return '<p>' . $section . '</p>';
            }, $sections)) .
        '</div>';
}

function make_literal($plain)
{
    return htmlspecialchars($plain, ENT_COMPAT, mb_internal_encoding());
}

function make_page($blocks)
{
    return '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<style type="text/css">
			body {
				padding: 8px;
				margin: 0;
				background: #E7EBF7;
				font: normal normal normal 14px verdana, arial, Helvetica, sans-serif;
				color: #333333;
			}

			blockquote {
				padding: 8px;
				margin: 8px;
				background: #EDEDED;
				border: 1px solid #555555;
			}

			blockquote .section {
				margin: 16px 0 0 0;
			}

			blockquote .section:first-child {
				margin: 0;
			}

			blockquote .section .field {
				display: flex;
				align-items: center;
				margin: 8px 0;
			}

			blockquote .section .field .caption {
				flex: 1;
				margin: 0 8px;
			}

			blockquote .section .field .help {
				flex: 3;
				margin: 0 8px;
				font-size: 80%;
			}

			blockquote .section .field .input {
				flex: 2;
				margin: 0 8px;
			}

			blockquote .section h2 {
				padding: 0;
				margin: 0;
				font-size: 140%;
				font-style: italic;
				font-weight: bold;
			}

			blockquote .section p {
				padding: 0;
				margin: 16px 0 0 0;
			}

			blockquote .section textarea {
				margin: 16px 0 0 0;
				width: 100%;
				box-sizing: border-box;
			}

			form {
				margin: 0;
            }

            .status-failure h2,
            .status-failure p {
				color: #A00;
			}

            .status-success h2,
            .status-success p {
				color: #060;
			}
		</style>
		<title>yAronet setup script</title>
	</head>
	<body>
		' . implode('', $blocks) . '
	</body>
</html>';
}

mb_internal_encoding('utf-8');

if (@include './config.php') {
    $blocks = array(make_block_advice(array(make_section_text('Configuration file found', array(
        'It seems there is already a configuration file (`config.php`) for this website.',
        'For security reasons, install script can only be used when website is not configured. Please rename or delete this file if you want to use install script.'
    )))));
} else {
    $templates = array(
        'static/.htaccess' => @file_get_contents(dirname(__FILE__) . '/static/.htaccess.dist'),
        '.htaccess' => @file_get_contents(dirname(__FILE__) . '/.htaccess.dist'),
        'config.php' => @file_get_contents(dirname(__FILE__) . '/config.php.dist')
    );

    if (in_array(false, $templates, true)) {
        $blocks = array(make_block_status('failure', array(make_section_text('Configuration templates missing', array(
            'Couldn\'t find required configuration template files: ' . implode(', ', array_map(function ($name) {
                return $name . '.dist';
            }, array_keys($templates))) . '.',
            'Please make sure both files exist in same folder than install script (`install.php`) and can be read by current user account.'
        )))));
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $error = null;

        if (isset($_POST['meta_cache_disable']) && $_POST['meta_cache_disable']) {
            $_POST['engine_network_route_cache'] = null;
            $_POST['engine_text_display_cache'] = null;
            $_POST['engine_text_i18n_cache'] = null;
        }

        foreach ($_POST as $key => $value) {
            if ($error !== null) {
                break;
            }

            switch ($key) {
                case 'engine_network_route_page':
                    if ($value !== '' && substr($value, 0, 1) !== '/') {
                        $error = 'Invalid base path for page URLs, it must begin with a "/" character.';
                    }

                    break;

                case 'engine_network_sql_connection':
                    require './library/redmap/redmap.php';

                    try {
                        $sql = RedMap\open($value);

                        if ($sql->connect()) {
                            $language = isset($_POST['engine_text_i18n_language']) ? $_POST['engine_text_i18n_language'] : 'en';
                            $time = time();
                            $unique = uniqid();

                            $sql->client->execute('REPLACE INTO `account_user` (`id`, `login`, `email`, `mechanism`, `secret`, `create_time`, `pulse_time`, `recover_time`, `language`, `template`, `is_active`, `is_admin`, `is_disabled`, `is_favorite`, `is_uniform`, `options`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array(
                                1,
                                isset($_POST['meta_admin_login']) ? $_POST['meta_admin_login'] : 'admin',
                                'admin@' . $_SERVER['SERVER_NAME'],
                                'sha256',
                                isset($_POST['meta_admin_password']) ? implode(':', array('sha256', $unique, hash_hmac('sha256', $_POST['meta_admin_password'], $unique))) : '',
                                $time,
                                $time,
                                0,
                                $language,
                                'html.kyanite',
                                1,
                                1,
                                0,
                                1,
                                0,
                                ''
                            ));

                            $sql->client->execute('REPLACE INTO `board_profile` (`user`, `forum`, `gender`, `signature`, `avatar`, `avatar_tag`) VALUES (?, ?, ?, ?, ?, ?)', array(
                                1,
                                null,
                                2,
                                '!|',
                                0,
                                0
                            ));

                            $pages = array(
                                array(
                                    'contacts',
                                    $language,
                                    'Contacts',
                                    '!|This page should contain contact information about website owners. Use administrator account to edit its content.'
                                ),
                                array(
                                    'guides',
                                    $language,
                                    'Guides',
                                    '!|This page should contain user guides to know how to use website. Use administrator account to edit its content.'
                                ),
                                array(
                                    'rules',
                                    $language,
                                    'Rules',
                                    '!|This page should contain rules to be followed on website. Use administrator account to edit its content.'
                                )
                            );

                            foreach ($pages as $page) {
                                $sql->client->execute('INSERT IGNORE INTO `help_page` (`label`, `language`, `name`, `text`) VALUES (?, ?, ?, ?)', $page);
                            }
                        } else {
                            $error = 'Cannot connect to database, please make sure your SQL connection string is valid.';
                        }
                    } catch (Exception $exception) {
                        $error = 'SQL connection string is invalid (' . $exception->getMessage() . ')';
                    }

                    break;

                case 'engine_network_http_insecure':
                case 'engine_text_display_use-less':
                    $value = (bool)$value;

                    break;

                case 'engine_service_recaptcha_site-key':
                case 'engine_service_recaptcha_site-secret':
                case 'engine_service_twitter_consumer-key':
                case 'engine_service_twitter_consumer-secret':
                case 'engine_service_twitter_token-key':
                case 'engine_service_twitter_token-secret':
                    if ($value === '') {
                        $value = null;
                    }

                    break;

                case 'engine_system_encoding_charset':
                    if (!in_array(strtolower($value), array_map('strtolower', mb_list_encodings()))) {
                        $error = 'Selected encoding is either invalid or not supported on your system.';
                    }

                    break;

                case 'engine_system_locale_name':
                    if (@setlocale(LC_ALL, $value) === false) {
                        $error = 'Selected locale doesn\'t exist in your system. Please make sure it is valid or run `sudo locale-gen ' . make_literal($value) . '` on your server if needed.';
                    }

                    break;

                case 'meta_admin_login':
                    if ($value === '') {
                        $error = 'Administrator login must not be empty.';
                    }

                    continue 2;

                case 'meta_admin_password':
                    if ($value === '') {
                        $error = 'Administrator password must not be empty.';
                    }

                    continue 2;

                case 'meta_cache_disable':
                    continue 2;

                case 'version':
                    $value = (int)$value;

                    break;
            }

            if ($error === null) {
                // PHP replaces dots by underscores in POST variable names, so this
                // script uses dashes instead and replaces them back to dots when
                // modifying configuration file.
                $pattern = preg_quote(str_replace('_', '.', $key), '@');

                $templates['static/.htaccess'] = preg_replace('@{{' . $pattern . '}}@m', $value, $templates['static/.htaccess']);
                $templates['.htaccess'] = preg_replace('@{{' . $pattern . '}}@m', $value, $templates['.htaccess']);
                $templates['config.php'] = preg_replace('@^(\\s*)(?://\\s*)?(\'' . $pattern . '\'\\s*=>).*$@m', '\\1\\2 ' . var_export($value, true) . ',', $templates['config.php'], 1, $count);

                if (in_array(false, $templates, true)) {
                    $error = 'Internal error, could not parse configuration file. Please report this error to yAronet <a href="https://github.com/r3c/yaronet/issues">GitHub issues</a>.';
                } elseif ($count !== 1) {
                    $error = 'Configuration key &quot;' . $key . '&quot; was not recognized.';
                }
            }
        }

        if (isset($_POST['version']) && $error === null) {
            $saved = true;

            foreach ($templates as $name => $contents) {
                $saved = @file_put_contents(dirname(__FILE__) . '/' . $name, $contents) !== false && $saved;
            }

            if ($saved) {
                $section = make_section_text(
                    'Configuration saved',
                    array(
                        'Your configuration was successfully validated and saved to files. If you are using a different web server than Apache HTTPd then you will need additional configuration for the URL rewriting to work properly. Please read `INSTALL.md` file for more information.',
                        'If you want to run this script again, delete file `config.php` from your server first. Otherwise you can now safely delete file `install.php`.',
                        'Follow <a href=".">this link</a> and use the administrator login and password you provided to login.'
                    )
                );
            } else {
                $section = make_section_config(
                    'Configuration ready',
                    'Your configuration has been validated, but install script cannot save it as files due to permission restrictions. Please create them manually using following contents:',
                    $templates
                );
            }

            $blocks = array(make_block_status('success', array($section)));
        } else {
            $base_url = preg_match('@^(.*)/install\\.php([?#]|$)@', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $match) ? $match[1] : '';

            $blocks = array(
                $error !== null ? make_block_status('failure', array(make_section_text('Configuration error', array($error)))) : '',
                make_block_form(true, array(
                    make_section_form('Administrator account', array(
                        array(
                            'name' => 'meta_admin_login',
                            'caption' => 'Administrator login:',
                            'help' => 'An administrator account will be created with this login and will have full access on the website, as well as ability to switch other accounts to administrators.',
                            'text' => 'admin'
                        ),
                        array(
                            'name' => 'meta_admin_password',
                            'caption' => 'Administrator password:',
                            'help' => 'Password of the administrator account. There is no restriction on the password format, however you should select a strong one and keep it safe.',
                            'password' => ''
                        )
                    )),
                    make_section_form('Network configuration', array(
                        array(
                            'name' => 'engine_network_sql_connection',
                            'caption' => 'SQL connection string:',
                            'help' => 'Expected format is &quot;mysqli://&lt;user&gt;:&lt;password&gt;@&lt;hostname&gt;/&lt;database&gt;&quot;. You can append a query string to specify optional parameters, such as "charset" to define default character set for the connection.',
                            'text' => 'mysqli://yaronet@localhost/yaronet?charset=utf8mb4'
                        ),
                        array(
                            'name' => 'engine_network_route_page',
                            'caption' => 'Base path for URLs:',
                            'help' => 'Base path used to build and parse URLs to all website pages. This parameter must begin with a &quot;/&quot; character and exactly match your HTTP server configuration, otherwise yAronet URL rewriting system won\'t work.',
                            'text' => $base_url
                        ),
                        array(
                            'name' => 'engine_network_route_static',
                            'caption' => 'Base URL for assets:',
                            'help' => 'Base URL used to build URLs to all website static assets. You can reuse base path for URLs and append &quot;static&quot; to it, or specify an URL to some other server if you want to serve static assets from another server.',
                            'text' => $base_url ? $base_url . '/static' : ''
                        )
                    )),
                    make_section_form('Text configuration', array(
                        array(
                            'name' => 'engine_text_i18n_language',
                            'caption' => 'Default language:',
                            'help' => 'Language used as a fallback when no compatible language could be deduced from user agent. Registered users will be able to manually override this parameter in their account settings.',
                            'options' => array('en' => 'English', 'fr' => 'Français')
                        ),
                        array(
                            'name' => 'engine_system_encoding_charset',
                            'caption' => 'Text encoding:',
                            'help' => 'Character set and encoding used to display website pages.',
                            'text' => 'utf-8'
                        ),
                        array(
                            'name' => 'engine_system_locale_name',
                            'caption' => 'System locale:',
                            'help' => 'Text locale used by PHP for text processing. You must select a locale using an encoding compatible with the one you selected for display, otherwise some characters may get corrupted.',
                            'text' => 'en_US.utf8'
                        )
                    )),
                    make_section_form('Visual configuration', array(
                        array(
                            'name' => 'engine_text_display_logo',
                            'caption' => 'Default logo HTML:',
                            'help' => 'HTML code displayed as default website logo when no custom header is defined on current forum. This value must be a valid HTML code snippet and can include macros: {home} for website home page, {user} for current user identifier.',
                            'text' => $base_url ? '<img class="default-mascot" src="' . $base_url . '/static/image/mascot.png" /> <a class="default-name" href="{home}"></a>' : ''
                        )
                    )),
                    make_section_form('Service configuration', array(
                        array(
                            'name' => 'engine_service_recaptcha_site-key',
                            'caption' => 'reCaptcha site key:',
                            'help' => 'Site key for reCaptcha service, used for user registration. You can register a new reCaptcha account <a href="https://www.google.com/recaptcha">here</a> if needed or leave this option empty to disable reCaptcha.',
                            'text' => ''
                        ),
                        array(
                            'name' => 'engine_service_recaptcha_site-secret',
                            'caption' => 'reCaptcha secret key:',
                            'help' => 'Secret key for reCaptcha service, leave this option empty to disable reCaptcha.',
                            'text' => ''
                        ),
                        array(
                            'name' => 'engine_service_twitter_consumer-key',
                            'caption' => 'Twitter API key:',
                            'help' => 'Twitter API key, used for embedding tweets in messages using Twitter API. You can register a new Twitter application <a href="https://developer.twitter.com/">here</a> if needed or leave this option empty to disable Twitter integration.',
                            'text' => ''
                        ),
                        array(
                            'name' => 'engine_service_twitter_consumer-secret',
                            'caption' => 'Twitter API secret:',
                            'help' => 'Twitter API key secret, leave this option empty to disable Twitter integration.',
                            'text' => ''
                        ),
                        array(
                            'name' => 'engine_service_twitter_token-key',
                            'caption' => 'Twitter access token:',
                            'help' => 'Twitter access token, leave this option empty to disable Twitter integration.',
                            'text' => ''
                        ),
                        array(
                            'name' => 'engine_service_twitter_token-secret',
                            'caption' => 'Twitter token secret:',
                            'help' => 'Twitter access token secret, leave this option empty to disable Twitter integration.',
                            'text' => ''
                        )
                    )),
                    make_section_form('Debug configuration', array(
                        array(
                            'name' => 'engine_text_display_use-less',
                            'caption' => 'Client-side LESS:',
                            'help' => 'Defines if LESS files are processed on-the-fly for debugging or pre-processed during deployment. To enable pre-compiled CSS mode you need to compile LESS files into CSS as described in INSTALL.md.',
                            'options' => array('0' => 'Disable and use pre-compiled CSS files', '1' => 'Enable (do not use in production)')
                        ),
                        array(
                            'name' => 'engine_network_http_insecure',
                            'caption' => 'Insecure pages:',
                            'help' => 'Defines if sensitive pages (e.g. user authentication) can be accessed through HTTP or if an upgrade to HTTPs is performed by website. Enabling HTTPs is required on production but requires a valid SSL certificate.',
                            'options' => array('0' => 'Disable and force HTTPs when needed', '1' => 'Enable (do not use in production)')
                        ),
                        array(
                            'name' => 'meta_cache_disable',
                            'caption' => 'Disable all caching:',
                            'help' => 'Disable all caching mechanism so that any change to codebase is visible on refresh. This feature will make all pages much slower and must never be enabled in production.',
                            'options' => array('0' => 'Disable and cache strings, templates & routes', '1' => 'Enable (do not use in production)')
                        ),
                        array(
                            'name' => 'version',
                            'text' => '1'
                        )
                    ))
                ))
            );
        }
    } else {
        $blocks = array(
            make_block_advice(array(make_section_text(
                'yAronet install script',
                array(
                    'You are about to configure yAronet. This script will help you generate a valid configuration that must be saved to files on your server.',
                    'Please use one of the buttons below to begin configuration using a preset suitable for target environnement:' .
                    '<ul>' .
                        '<li>Preset &quot;development&quot; requires less configuration but is missing mandatory parameters for your website performace and safety ;</li>' .
                        '<li>Preset &quot;production&quot; implies you follow extra deployment steps described in `INSTALL.md` document.</li>' .
                    '</ul>',
                    'Once you are done with this step your website should be working with minimal setup and this script won\'t be available anymore. You will always be able to either manually edit file `config.php` to update your configuration or delete the file and run this script again to reset all settings.'
                )
            ))),
            make_block_form(true, array(make_section_form('Setup for development', array(
                array(
                    'name' => 'engine_text_display_use-less',
                    'text' => '1'
                ),
                array(
                    'name' => 'engine_network_http_insecure',
                    'text' => '1'
                ),
                array(
                    'name' => 'meta_cache_disable',
                    'text' => '1'
                )
            )))),
            make_block_form(true, array(make_section_form('Setup for production', array(
            ))))
        );
    }
}

echo make_page($blocks);
