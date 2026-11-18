<?php

/*
|--------------------------------------------------------------------------
| Authentication Lines (Indonesian)
|--------------------------------------------------------------------------
|
| The app renders `trans('auth.failed')` on a rejected sign-in attempt. With
| no id/ translation that key printed literally as "auth.failed", so the one
| message every visitor sees on a typo was a raw string. Same keys Laravel's
| authentication uses; the throttle line fires after five failed attempts.
|
*/

return [
    'failed' => 'Email atau kata sandi tidak cocok dengan data kami.',
    'password' => 'Kata sandi yang dimasukkan salah.',
    'throttle' => 'Terlalu banyak percobaan masuk. Silakan coba lagi dalam :seconds detik.',
];
