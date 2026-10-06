<?php
/**
 * response.php – Practical 7
 * Helpers to redirect back with flash messages stored in session.
 */

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    http_response_code(403); exit('Access denied.');
}

/**
 * Store a flash message in session and redirect.
 *
 * @param string $redirectTo  URL to redirect to
 * @param bool   $success     true = success, false = error
 * @param string $message     Main message text
 * @param array  $errors      Field-level errors  ['field' => 'error msg']
 * @param array  $oldInput    Repopulate form values ['field' => 'value']
 */
function redirect_with_flash(
    string $redirectTo,
    bool   $success,
    string $message,
    array  $errors   = [],
    array  $oldInput = []
): void {
    $_SESSION['flash'] = [
        'success'  => $success,
        'message'  => $message,
        'errors'   => $errors,
        'oldInput' => $oldInput,
    ];
    header('Location: ' . $redirectTo);
    exit;
}

/**
 * Retrieve and clear the flash message from session.
 *
 * @return array|null  Flash data or null if none
 */
function get_flash(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Get a single old input value from flash (for form repopulation).
 *
 * @param string $field
 * @param string $default
 * @return string
 */
function old(string $field, string $default = ''): string {
    $flash = $_SESSION['flash']['oldInput'] ?? [];
    $val   = $flash[$field] ?? $default;
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

/**
 * Get a field error from flash.
 *
 * @param string $field
 * @return string  HTML error span or ''
 */
function field_error(string $field): string {
    $errors = $_SESSION['flash']['errors'] ?? [];
    if (empty($errors[$field])) return '';
    $msg = htmlspecialchars($errors[$field], ENT_QUOTES, 'UTF-8');
    return "<span class=\"field-err\" role=\"alert\">⚠ {$msg}</span>";
}

/**
 * Render the flash alert box (success / error banner).
 *
 * @param array $flash  from get_flash()
 * @return string HTML
 */
function render_flash(array $flash): string {
    $type  = $flash['success'] ? 'alert-success' : 'alert-error';
    $icon  = $flash['success'] ? '✅' : '❌';
    $msg   = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
    $html  = "<div class=\"alert {$type}\" role=\"alert\" aria-live=\"polite\">";
    $html .= "<span class=\"alert-icon\" aria-hidden=\"true\">{$icon}</span> {$msg}";

    if (!empty($flash['errors'])) {
        $html .= '<ul class="error-list">';
        foreach ($flash['errors'] as $err) {
            $html .= '<li>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $html .= '</ul>';
    }

    $html .= '</div>';
    return $html;
}
