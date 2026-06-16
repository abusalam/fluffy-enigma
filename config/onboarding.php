<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Onboarding secret
    |--------------------------------------------------------------------------
    |
    | The first-run setup wizard is reachable ONLY at /setup/{secret}, where
    | {secret} must match this value. Keep it long and unguessable. Until the
    | wizard is completed (a super-admin exists and the "onboarding_completed"
    | setting is true) the public sees the "Under construction" page.
    |
    */

    'secret' => env('APP_ONBOARDING_SECRET', ''),

    /*
    | Default, editable onboarding checklist presented to the operator on the
    | final wizard step. Stored into settings so it can be toggled later.
    */
    'default_steps' => [
        ['key' => 'branding', 'label' => 'Upload organisation logo & set portal name', 'done' => false],
        ['key' => 'superuser', 'label' => 'Create the super administrator account', 'done' => false],
        ['key' => 'roles', 'label' => 'Review default roles & permissions', 'done' => false],
        ['key' => 'links', 'label' => 'Create your first short link', 'done' => false],
    ],
];
