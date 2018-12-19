<?php

defined('YARONET') or die;

Glay\using('yN\\Entity\\Help\\Page', './entity/help/page.php');

function page_edit($request, $logger, $sql, $display, $input, $user)
{
    if (!$user->is_admin) {
        return Glay\Network\HTTP::code(403);
    }

    $label = (string)$request->get_or_fail('label');
    $language = (string)$request->get_or_default('language', $user->language);

    $page = yN\Entity\Help\Page::get_by_label($sql, $label, $language) ?: new yN\Entity\Help\Page();
    $page->label = $label;
    $page->language = $language;

    if ($request->method === 'POST') {
        $alerts = array();

        if ($input->get_string('name', $name)) {
            $page->name = $name;
        }

        if ($input->get_string('text', $text)) {
            $page->convert_text($text, $request->router, $logger);
        }

        if (!$page->save($sql, $alert)) {
            $alerts[] = $alert;
        }
    } else {
        $alerts = null;
    }

    $input->ensure('name', $page->name);
    $input->ensure('text', $page->revert_text());

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-help-page-edit.deval', 'help.page.' . $page->label . '.edit', array(
        'alerts'	=> $alerts,
        'page'		=> $page
    )));
}

function page_view($request, $logger, $sql, $display, $input, $user)
{
    $label = (string)$request->get_or_fail('label');
    $language = (string)$request->get_or_default('language', $user->language);

    $page = yN\Entity\Help\Page::get_by_label($sql, $label, $language);

    if ($page === null) {
        $default = yN\Engine\Text\Internationalization::default_language();

        if ($language !== $default) {
            $url = $request->router->url('help.page.view', array('label' => $label, 'language' => $default));
        } elseif ($user->is_admin) {
            $url = $request->router->url('help.page.edit', array('label' => $label, 'language' => $default));
        } else {
            return Glay\Network\HTTP::code(404);
        }

        return Glay\Network\HTTP::go($url);
    }

    // Render template
    return Glay\Network\HTTP::data($display->render('yn-help-page-view.deval', 'help.page.' . $page->label, array(
        'page'	=> $page
    )));
}
