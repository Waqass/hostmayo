<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanilla-smarty
 * @since 2.0
 */

/**
 * Returns the  custom text from a theme.
 *
 * @param array $params The parameters passed into the function. This currently takes no parameters.
 *  - <b>code</b>: The text code set in the theme's information.
 *  - <b>default</b>: The default text if the user hasn't overridden.
 * @param Smarty $smarty The smarty object rendering the template.
 * @return The text.
 */
function smarty_function_t($params, &$smarty) {
    $code = val('c', $params, '');
    $result = t($code, val('d', $params, $code));
	return $result;
}
