<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Board\\Ban', './entity/board/ban.php');
Glay\using('yN\\Entity\\Board\\Bookmark', './entity/board/bookmark.php');
Glay\using('yN\\Entity\\Board\\Log', './entity/board/log.php');
Glay\using('yN\\Entity\\Board\\Permission', './entity/board/permission.php');
Glay\using('yN\\Entity\\Board\\Post', './entity/board/post.php');
Glay\using('yN\\Entity\\Board\\Reference', './entity/board/reference.php');
Glay\using('yN\\Entity\\Board\\Section', './entity/board/section.php');
Glay\using('yN\\Entity\\Board\\Subscription', './entity/board/subscription.php');
Glay\using('yN\\Entity\\Board\\Topic', './entity/board/topic.php');

function topic_delete($request, $logger, $sql, $display, $input, $user)
{
    global $time;

    $topic = yN\Entity\Board\Topic::get_by_identifier($sql, (int)$request->get_or_fail('topic'), null);

    if ($topic === null) {
        return Glay\Network\HTTP::code(404);
    }

    if (!$topic->allow_edit(yN\Entity\Board\Permission::check_by_topic($sql, $user, $topic->section, $topic))) {
        return Glay\Network\HTTP::code(403);
    }

    // Get current or parent forum
    $forum_id = (int)$request->get_or_default('forum', $topic->section->forum_id);

    if ($topic->section->forum_id !== $forum_id) {
        $forum = yN\Entity\Board\Forum::get_by_identifier($sql, $forum_id);
    } else {
        $forum = $topic->section->forum;
    }

    if ($forum === null) {
        return Glay\Network\HTTP::code(404);
    }

    // Submit changes
    if ($request->method === 'POST') {
        $alerts = array();

        if ($topic->section_id !== 10) {
            $old_section = $topic->section_id;

            $topic->last_time = $time;
            $topic->section_id = 10;
            $topic->weight = 0;

            if (!$topic->save($sql, $alert)) {
                $alerts[] = $alert;
            } else {
                yN\Entity\Board\Bookmark::delete_by_topic($sql, $topic->id);
                yN\Entity\Board\Section::invalidate($sql, $old_section);
            }
        } else {
            if (!$topic->delete($sql, $alert)) {
                $alerts[] = $alert;
            }
        }

        if (count($alerts) === 0) {
            yN\Entity\Board\Log::push($sql, $forum->id, $user->id, 'topic.delete', $topic->get_primary());
        }
    } else {
        $alerts = null;
    }

    // Render template
    $location = 'board.forum.' . $forum->id . '.' . $topic->section_id . '.' . $topic->id . '.delete';

    return Glay\Network\HTTP::data($display->render('yn-board-topic-delete.deval', $location, array(
        'alerts' => $alerts,
        'forum' => $forum,
        'topic' => $topic
    ), $forum->template));
}

function topic_edit($request, $logger, $sql, $display, $input, $user)
{
    global $address;
    global $time;

    if ($user->id === null) {
        return Glay\Network\HTTP::code(401);
    }

    $source_id = $request->get_or_default('source');
    $topic_id = $request->get_or_default('topic');

    // Get or create requested topic and associated permissions
    if ($topic_id !== null) {
        $source = null;
        $topic = yN\Entity\Board\Topic::get_by_identifier($sql, (int)$topic_id, null);

        if ($topic === null) {
            return Glay\Network\HTTP::code(404);
        }

        $section = $topic->section;
        $access = yN\Entity\Board\Permission::check_by_topic($sql, $user, $section, $topic);

        if (!$topic->allow_edit($access)) {
            return Glay\Network\HTTP::code(403);
        }

        $data = array_diff_key($topic->revert(), $topic->get_primary());
    } elseif ($source_id !== null) {
        $source = yN\Entity\Board\Topic::get_by_identifier($sql, (int)$source_id, null);

        if ($source === null) {
            return Glay\Network\HTTP::code(404);
        }

        $section = $source->section;
        $access = yN\Entity\Board\Permission::check_by_topic($sql, $user, $section, $source);

        if (!$section->allow_publish($access)) {
            return Glay\Network\HTTP::code(403);
        }

        $topic = new yN\Entity\Board\Topic();
        $topic->section_id = $section->id;

        $data = array();
    } else {
        $section = yN\Entity\Board\Section::get_by_identifier($sql, (int)$request->get_or_fail('section'));
        $source = null;

        if ($section === null) {
            return Glay\Network\HTTP::code(404);
        }

        $access = yN\Entity\Board\Permission::check_by_section($sql, $user, $section);

        if (!$section->allow_publish($access)) {
            return Glay\Network\HTTP::code(403);
        }

        $topic = new yN\Entity\Board\Topic();
        $topic->section_id = $section->id;

        $data = array();
    }

    // Get specified or parent forum
    $forum_id = (int)$request->get_or_default('forum', $section->forum_id);

    if ($section->forum_id !== $forum_id) {
        $forum = yN\Entity\Board\Forum::get_by_identifier($sql, $forum_id);
    } else {
        $forum = $section->forum;
    }

    if ($forum === null) {
        return Glay\Network\HTTP::code(404);
    }

    // Reject user if a ban is active
    if (yN\Entity\Board\Ban::check($sql, $forum->id, $address->string)) {
        return Glay\Network\HTTP::code(403);
    }

    // Submit changes
    $new = $topic->id === null;

    if ($request->method === 'POST') {
        $alerts = array();
        $old_section_id = null;

        // Disable edit for unconfirmed users
        if (!$user->is_active) {
            $alerts[] = 'user-active';
        }

        // Flags
        if ($topic->allow_edit($access) && $input->get_array('flags', $flags)) {
            $topic->is_closed = isset($flags['closed']);
        }

        // Name
        if ($input->get_string('name', $name)) {
            $topic->convert_name($name, $request->router, $logger);
        }

        // Section
        if ($input->get_string('section', $section_unique)) {
            $move_section = yN\Entity\Board\Section::get_by_unique($sql, $section_unique);

            if ($move_section === null) {
                $alerts[] = 'section-unknown';
            } elseif (!$move_section->allow_publish(yN\Entity\Board\Permission::check_by_section($sql, $user, $move_section))) {
                $alerts[] = 'section-access';
            } elseif ($move_section->id !== $topic->section_id) {
                $old_section_id = $topic->section_id;

                $section = $move_section;
                $topic->section_id = $section->id;
            }
        }

        // Text
        if ($input->get_string('text', $text) && $new && $source === null) {
            // FIXME: duplicate of Post::save method to avoid creating empty topic [empty-topic]
            $blank_length = strlen(yN\Engine\Text\Markup::blank());
            $text_length = strlen($text);

            if ($text_length < $blank_length + 1 || $text_length > 32767) {
                $alerts[] = 'post-text-length';
            }
        } else {
            $text = null;
        }

        // Weight
        if ($topic->allow_edit($access) && $input->get_number('weight', $weight)) {
            $topic->weight = max(min($weight, yN\Entity\Board\Topic::WEIGHT_MAX), yN\Entity\Board\Topic::WEIGHT_MIN);
        }

        // Drift
        if ($input->get_string('positions', $positions_string) && $new && $source !== null) {
            $positions = array();

            foreach (array_filter(array_map('trim', explode(',', $positions_string)), 'strlen') as $chunk) {
                $bounds = array_map(function ($bound) {
                    return (int)$bound;
                }, explode('-', $chunk, 2));

                if (count($bounds) < 2) {
                    $bounds[] = $bounds[0];
                }

                if ($bounds[0] === 0 || $bounds[1] === 0 || $bounds[0] > $bounds[1] || $bounds[1] - $bounds[0] + 1 > yN\Entity\Board\Topic::DRIFT_MAX) {
                    $alerts[] = 'positions-invalid';
                }

                for ($i = $bounds[0]; $i <= $bounds[1]; ++$i) {
                    $positions[] = $i;
                }
            }

            $positions = array_unique($positions, SORT_NUMERIC);

            sort($positions);

            if (count($positions) > yN\Entity\Board\Topic::DRIFT_MAX) {
                $alerts[] = 'positions-invalid';
            } elseif (count($positions) < 1) {
                $alerts[] = 'positions-empty';
            }

            $references = yN\Entity\Board\Reference::get_by_positions($sql, $source->id, $positions, yN\Entity\Board\Topic::DRIFT_MAX);

            if (count($positions) !== count($references)) {
                $alerts[] = 'positions-incomplete';
            }
        } else {
            $references = null;
        }

        // Save
        if (count($alerts) === 0) {
            if (!$topic->save($sql, $alert)) {
                $alerts[] = $alert;
            } else {
                if ($new) {
                    $source_position = null;
                    $topic_position = null;

                    // Duplicate references from source topic if any
                    if ($references !== null && $source !== null) {
                        $i18n = new yN\Engine\Text\Internationalization($user->language);

                        // Append Boo message to both source and new topics
                        $position = count($references) > 0 ? $references[count($references) - 1]->position : 1;

                        $post = new yN\Entity\Board\Post();
                        $post->create_profile_id = $user->id;
                        $post->convert_text($i18n->format('yn.board.topic.edit.drift', array(
                            'source' => Glay\Network\URI::here()->combine($request->router->url('board.topic.view', array('topic' => $source->id, 'topic_hint' => $source->hint, 'page' => yN\Entity\Board\Topic::get_page($position), '_template' => null), 'post-' . $position)),
                            'topic' => Glay\Network\URI::here()->combine($request->router->url('board.topic.view', array('topic' => $topic->id, 'topic_hint' => $topic->hint, '_template' => null))),
                            'user' => $user
                        )), $request->router, $logger, $source->id);

                        if (!$post->save($sql, $alert)) {
                            $alerts[] = 'post-' . $alert;
                        } else {
                            $reference = new yN\Entity\Board\Reference();
                            $reference->post_id = $post->id;
                            $reference->topic_id = $source->id;

                            if (!$reference->save($sql, $alert)) {
                                $alerts[] = 'reference-' . $alert;
                            } else {
                                $source_position = $reference->position;
                            }

                            $reference = new yN\Entity\Board\Reference();
                            $reference->post_id = $post->id;
                            $reference->topic_id = $topic->id;

                            if (!$reference->save($sql, $alert)) {
                                $alerts[] = 'reference-' . $alert;
                            } else {
                                $topic_position = $reference->position;
                            }
                        }

                        // Clone references to new topic
                        foreach ($references as $reference) {
                            $reference->position = null;
                            $reference->topic_id = $topic->id;

                            if (!$reference->save($sql, $alert)) {
                                $alerts[] = 'reference-' . $alert;
                            } else {
                                $topic_position = $reference->position;
                            }
                        }

                        // Save both source and new topics
                        $source->last_time = $time;
                        $source->save($sql, $alert);

                        $topic->last_time = $time;
                        $topic->save($sql, $alert);
                    }

                    // Create post and watch topic
                    if ($text !== null) {
                        $post = new yN\Entity\Board\Post();
                        $post->create_profile_id = $user->id;
                        $post->convert_text($text, $request->router, $logger, $topic->id);

                        $reference = new yN\Entity\Board\Reference();

                        if (!$post->save($sql, $alert)) {
                            $alerts[] = 'post-' . $alert;
                        } else {
                            $reference->post_id = $post->id;
                            $reference->topic_id = $topic->id;

                            if (!$reference->save($sql, $alert)) {
                                $alerts[] = 'reference-' . $alert;
                            } else {
                                $topic_position = $reference->position;
                            }
                        }
                    }

                    // Invalidate sibling entities
                    if ($source_position !== null && $source !== null) {
                        yN\Entity\Board\Section::set_fresh($sql, $source->section_id, $user->id);
                        yN\Entity\Board\Subscription::set_position($sql, $source->section_id, $source->id, $source_position - 1);
                        yN\Entity\Board\Bookmark::set_fresh($sql, $source->id, $source_position);
                        yN\Entity\Board\Bookmark::set_watch($sql, $user->id, $source->id, $source_position, false, true);
                    }

                    if ($topic_position !== null) {
                        yN\Entity\Board\Section::set_fresh($sql, $topic->section_id, $user->id);
                        yN\Entity\Board\Subscription::set_position($sql, $topic->section_id, $topic->id, $topic_position - 1);
                        yN\Entity\Board\Bookmark::set_fresh($sql, $topic->id, $topic_position);
                        yN\Entity\Board\Bookmark::set_watch($sql, $user->id, $topic->id, $topic_position, false, true);
                    }

                    if ($source !== null && $source->section_id !== $topic->section_id) {
                        yN\Entity\Board\Section::invalidate($sql, $source->section_id);
                    }

                    yN\Entity\Board\Profile::invalidate($sql, $user->id);
                    yN\Entity\Board\Section::invalidate($sql, $topic->section_id);

                    // Grant permissions on topic if delegation is enabled
                    if ($section->is_delegated) {
                        $permission = yN\Entity\Board\Permission::fetch_by_profile_topic($sql, $user->id, $topic->id);
                        $permission->can_change = true;
                        $permission->can_read = true;
                        $permission->can_write = true;
                        $permission->save($sql, $alert);
                    }
                } elseif ($old_section_id !== null) {
                    yN\Entity\Board\Section::invalidate($sql, $old_section_id);
                    yN\Entity\Board\Section::invalidate($sql, $section->id);
                    yN\Entity\Board\Subscription::set_position($sql, $section->id, $topic->id, 1);
                }

                yN\Entity\Board\Log::push($sql, $forum->id, $user->id, 'topic.edit', array_merge(
                    array_diff_assoc($topic->revert(), $data),
                    $new ? array('new' => true) : array()
                ));
            }
        }
    } else {
        $alerts = null;
    }

    $input->ensure('flags', array('closed' => $topic->is_closed ?: null));
    $input->ensure('name', $topic->revert_name());
    $input->ensure('section', $section->get_unique());
    $input->ensure('weight', $topic->weight);

    // Render template
    $location = 'board.forum.' . $forum->id . '.' . $section->id . '.' . ($topic->id ?: 0) . '.edit';

    return Glay\Network\HTTP::data($display->render('yn-board-topic-edit.deval', $location, array(
        'access' => $access,
        'alerts' => $alerts,
        'forum' => $forum,
        'new' => $new,
        'section' => $section,
        'source' => $source,
        'topic' => $topic
    ), $forum->template));
}

function topic_view($request, $logger, $sql, $display, $input, $user)
{
    $page = max((int)$request->get_or_fail('page'), 1);

    // Get requested topic
    $topic = yN\Entity\Board\Topic::get_by_identifier($sql, (int)$request->get_or_fail('topic'), $user->id);

    if ($topic === null) {
        return Glay\Network\HTTP::code(404);
    }

    if ($request->get_or_default('topic_hint') !== $topic->hint) {
        return Glay\Network\HTTP::go($request->router->url('board.topic.view', array('forum' => $request->get_or_default('forum'), 'topic' => $topic->id, 'topic_hint' => $topic->hint, 'page' => $page)), Glay\Network\HTTP::REDIRECT_PERMANENT);
    }

    $section = $topic->section;

    // Get specified or parent forum
    $forum_id = (int)$request->get_or_default('forum', $section->forum_id);

    if ($section->forum_id !== $forum_id) {
        $forum = yN\Entity\Board\Forum::get_by_identifier($sql, $forum_id);
    } else {
        $forum = $section->forum;
    }

    if ($forum === null) {
        return Glay\Network\HTTP::code(404);
    }

    // Check permissions
    $access = yN\Entity\Board\Permission::check_by_topic($sql, $user, $section, $topic);

    if (!$topic->allow_view($access)) {
        yN\Entity\Board\Bookmark::delete_by_profile__topic($sql, $user->id, $topic->id);

        return Glay\Network\HTTP::code(403);
    }

    // Retrieve posts
    $references = yN\Entity\Board\Reference::get_by_topic($sql, $topic->id, $page - 1, $user->id, $page > 1 ? 1 : 0);

    if (count($references) === 0) {
        return Glay\Network\HTTP::code(404);
    }

    $last = 0;

    foreach ($references as $reference) {
        $last = max($reference->position, $last);
    }

    // Track
    if ($user->id !== null) {
        yN\Entity\Board\Bookmark::set_track($sql, $user->id, $topic->id, $last);
    }

    // Render template
    $location = 'board.forum.' . $forum->id . '.' . $topic->section_id . '.' . $topic->id;

    return Glay\Network\HTTP::data($display->render('yn-board-topic-view.deval', $location, array(
        'access' => $access,
        'forum' => $forum,
        'page' => $page,
        'references' => $references,
        'topic' => $topic
    ), $forum->template));
}
