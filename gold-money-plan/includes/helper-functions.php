<?php

function gmp_get_current_plan($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    return get_user_meta($user_id, 'gmp_plan', true);
}
