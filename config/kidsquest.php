<?php

return [
    'birthday_bonus' => (int) env('BIRTHDAY_BONUS_POINTS', 50),

    // Rentang nilai ulangan, urut menurun: [nilai minimal, huruf, poin]. Di bawah 70 tidak dapat poin.
    'exam_grades' => [[90, 'A', 5], [80, 'B', 4], [70, 'C', 3], [0, 'D', 0]],

    'avatars' => ['🦊', '🐼', '🐯', '🦁', '🐸', '🐵', '🦄', '🐙', '🐰', '🐻'],

    // Lama sesi (menit) dipakai config/session.php lewat SESSION_LIFETIME; 30 hari.
];
