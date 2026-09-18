<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                        |
// +---------------------------------------------------------------------------+
// | resolved_admin.php                                                         |
// |                                                                           |
// | Structured provider for the legacy Geeklog Core / Admin menu.             |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Convert a Geeklog-rendered administrative label into presentation-neutral
 * metadata. Some core/plugin callbacks use COM_createControl() and therefore
 * return small HTML wrappers (for example display-text-warning renders as
 * <span class="uk-text-danger">...</span> in Denim). The resolved-tree API
 * must not leak that theme HTML, but it should preserve the semantic intent.
 *
 * Geeklog/plugin option collection may HTML-encode that wrapper before Menu
 * receives it. Decode a bounded number of times before inspecting or stripping
 * markup so both raw and encoded controls are normalized consistently.
 *
 * @param string $label
 * @return array Array with plain-text label and optional semantic status
 */
function MENU_resolvedAdminLabelMetadata($label)
{
    $decoded = (string) $label;

    for ($i = 0; $i < 2; $i++) {
        $next = html_entity_decode($decoded, ENT_QUOTES, 'UTF-8');
        if ($next === $decoded) {
            break;
        }
        $decoded = $next;
    }

    $status = '';

    // Geeklog 2.2.x Denim renders COM_createControl('display-text-warning')
    // with uk-text-danger. Accept uk-text-warning as well for theme variants.
    if (preg_match('/class\s*=\s*(["\'])[^"\']*\buk-text-(?:danger|warning)\b[^"\']*\1/i', $decoded)) {
        $status = 'warning';
    } elseif (preg_match('/class\s*=\s*(["\'])[^"\']*\buk-text-success\b[^"\']*\1/i', $decoded)) {
        $status = 'success';
    } elseif (preg_match('/class\s*=\s*(["\'])[^"\']*\buk-text-(?:primary|muted)\b[^"\']*\1/i', $decoded)) {
        $status = 'info';
    }

    $plain = strip_tags($decoded);
    $plain = preg_replace('/\s+/u', ' ', $plain);
    $plain = trim((string) $plain);

    return array(
        'label' => $plain,
        'status' => $status,
    );
}

/**
 * Resolve the legacy Geeklog admin menu into presentation-neutral nodes.
 *
 * This deliberately mirrors the legacy visibility rules without returning
 * HTML. Themes remain responsible for markup, responsive behaviour and style.
 *
 * @return array
 */
function MENU_resolveGeeklogAdminChildren()
{
    global $_CONF, $_TABLES, $LANG01, $LANG29;

    $children = array();

    if (COM_isAnonUser()) {
        return $children;
    }

    $pluginOptions = function_exists('PLG_getAdminOptions') ? PLG_getAdminOptions() : array();
    $hasAdminEntry = SEC_isModerator()
        || SEC_hasRights('story.edit,block.edit,topic.edit,user.edit,plugin.edit,user.mail,syndication.edit', 'OR')
        || count($pluginOptions) > 0;

    if (!$hasAdminEntry) {
        return $children;
    }

    $controlLabel = isset($LANG29[34]) ? $LANG29[34] : 'Command & Control';
    $children[] = MENU_resolvedSyntheticNode($controlLabel, $_CONF['site_admin_url'] . '/index.php', 3, 'control');

    if (SEC_isModerator()) {
        $label = isset($LANG01[10]) ? $LANG01[10] : 'Submissions';
        $count = MENU_resolvedModerationCount();
        if ($count !== null) {
            $label .= ' (' . MENU_resolvedFormatCount($count) . ')';
        }
        $children[] = MENU_resolvedSyntheticNode($label, $_CONF['site_admin_url'] . '/moderation.php', 3, 'moderation');
    }

    if (SEC_hasRights('story.edit')) {
        $label = isset($LANG01[11]) ? $LANG01[11] : 'Stories';
        $count = MENU_resolvedTableCount(isset($_TABLES['stories']) ? $_TABLES['stories'] : '');
        if ($count !== null) {
            $label .= ' (' . MENU_resolvedFormatCount($count) . ')';
        }
        $children[] = MENU_resolvedSyntheticNode($label, $_CONF['site_admin_url'] . '/story.php', 3, 'stories');
    }

    if (SEC_hasRights('block.edit')) {
        $label = isset($LANG01[12]) ? $LANG01[12] : 'Blocks';
        $count = MENU_resolvedTableCount(isset($_TABLES['blocks']) ? $_TABLES['blocks'] : '');
        if ($count !== null) {
            $label .= ' (' . MENU_resolvedFormatCount($count) . ')';
        }
        $children[] = MENU_resolvedSyntheticNode($label, $_CONF['site_admin_url'] . '/block.php', 3, 'blocks');
    }

    if (SEC_hasRights('topic.edit')) {
        $label = isset($LANG01[13]) ? $LANG01[13] : 'Topics';
        $count = MENU_resolvedTableCount(isset($_TABLES['topics']) ? $_TABLES['topics'] : '');
        if ($count !== null) {
            $label .= ' (' . MENU_resolvedFormatCount($count) . ')';
        }
        $children[] = MENU_resolvedSyntheticNode($label, $_CONF['site_admin_url'] . '/topic.php', 3, 'topics');
    }

    if (SEC_hasRights('user.edit')) {
        $label = isset($LANG01[17]) ? $LANG01[17] : 'Users';
        $count = MENU_resolvedTableCount(isset($_TABLES['users']) ? $_TABLES['users'] : '');
        if ($count !== null) {
            $count = max(0, $count - 1);
            $label .= ' (' . MENU_resolvedFormatCount($count) . ')';
        }
        $children[] = MENU_resolvedSyntheticNode($label, $_CONF['site_admin_url'] . '/user.php', 3, 'users');
    }

    if (SEC_hasRights('group.edit')) {
        $label = isset($LANG01[96]) ? $LANG01[96] : 'Groups';
        $children[] = MENU_resolvedSyntheticNode($label, $_CONF['site_admin_url'] . '/group.php', 3, 'groups');
    }

    if (SEC_hasRights('user.mail')) {
        $label = isset($LANG01[105]) ? $LANG01[105] : 'Mail Users';
        $children[] = MENU_resolvedSyntheticNode($label, $_CONF['site_admin_url'] . '/mail.php', 3, 'mail');
    }

    if (SEC_hasRights('syndication.edit')) {
        $label = isset($LANG01[38]) ? $LANG01[38] : 'Syndication';
        $children[] = MENU_resolvedSyntheticNode($label, $_CONF['site_admin_url'] . '/syndication.php', 3, 'syndication');
    }

    if (SEC_hasRights('plugin.edit')) {
        $label = isset($LANG01[77]) ? $LANG01[77] : 'Plugins';
        $children[] = MENU_resolvedSyntheticNode($label, $_CONF['site_admin_url'] . '/plugins.php', 3, 'plugins');
    }

    foreach ($pluginOptions as $option) {
        if (!is_object($option)) {
            continue;
        }
        if (!isset($option->adminurl) || !isset($option->adminlabel)) {
            continue;
        }

        $metadata = MENU_resolvedAdminLabelMetadata($option->adminlabel);
        $label = $metadata['label'];
        if (isset($option->numsubmissions)) {
            $label .= ' (' . MENU_resolvedFormatCount((int) $option->numsubmissions) . ')';
        }

        $node = MENU_resolvedSyntheticNode(
            $label,
            (string) $option->adminurl,
            4,
            $metadata['label']
        );
        $node['status'] = $metadata['status'];
        $children[] = $node;
    }

    if (!empty($_CONF['sort_admin']) && count($children) > 1) {
        $control = array_shift($children);
        usort($children, 'MENU_resolvedAdminNodeSort');
        array_unshift($children, $control);
    }

    return $children;
}

function MENU_resolvedAdminNodeSort($a, $b)
{
    return strcasecmp($a['label'], $b['label']);
}

function MENU_resolvedTableCount($table)
{
    if ($table === '' || !function_exists('DB_count')) {
        return null;
    }
    return (int) DB_count($table);
}

function MENU_resolvedModerationCount()
{
    global $_CONF, $_TABLES;

    $count = 0;
    $hasCount = false;

    if (SEC_hasRights('story.moderate') && isset($_TABLES['storysubmission']) && function_exists('DB_count')) {
        $count += (int) DB_count($_TABLES['storysubmission']);
        $hasCount = true;
    }

    if (!empty($_CONF['commentsubmission']) && SEC_hasRights('comment.moderate')
        && isset($_TABLES['commentsubmissions']) && function_exists('DB_count')) {
        $count += (int) DB_count($_TABLES['commentsubmissions']);
        $hasCount = true;
    }

    if (!empty($_CONF['usersubmission']) && SEC_hasRights('user.edit,user.delete')
        && isset($_TABLES['users']) && function_exists('DB_count')) {
        $count += (int) DB_count($_TABLES['users'], 'status', '2');
        $hasCount = true;
    }

    if (function_exists('PLG_getSubmissionCount')) {
        $count += (int) PLG_getSubmissionCount();
        $hasCount = true;
    }

    return $hasCount ? $count : null;
}

function MENU_resolvedFormatCount($count)
{
    if (function_exists('COM_numberFormat')) {
        return COM_numberFormat((int) $count);
    }
    return (string) (int) $count;
}
