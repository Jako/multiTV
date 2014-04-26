<?php
if (isset($_POST['value']) && $_POST['value'] != '') {
    if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
        $_POST['value'] = stripslashes($_POST['value']);
    }
    $value = '{"fieldValue":[' . $_POST['value'] . ']}';

    // unmask MODX tags
    $unmasked = array('[', ']', '{', '}');
    $masked = array('&#x005B;', '&#x005D;', '&#x007B;', '&#x007D;');
    if (isset($sanitize_seed)) {
        $value = str_replace($sanitize_seed, '', $value);
    }
    $value = str_replace($masked, $unmasked, $value);

    // prepare TV value
    $multiTV->prepareValue($value);
    $answer['error'] = false;
    $answer['msg'] = $multiTV->tvValue;
} else {
    $answer['error'] = true;
    $answer['msg'] = 'No value to prepare';
}
