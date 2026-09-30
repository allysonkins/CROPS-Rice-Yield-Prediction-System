<?php

return [
    /*
    |------------------------------------------------------------------
    | Varieties that were included in the ML model's training data.
    | Adding a variety here without retraining has no effect.
    |------------------------------------------------------------------
    */
    'trained_varieties' => [
        'Angelica (NSIC Rc122)',
        'NSIC Rc216 (Tubigan 17)',
        'NSIC Rc 512 (Tubigan 44)',
        'NSIC RC 402 (Tubigan 36)',
        'NSIC Rc 534 (Salinas 29)',
        'NSIC 2016 Rc 456H (Mestiso 78)',
        'NSIC Rc234H (MESTISO 27)',
        'NSIC Rc 486 (Mestiso 80)',
        'NSIC Rc124H (MESTISO 4)',
        'NSIC Rc132H (MESTISO 6)',
    ],

    /*
    |------------------------------------------------------------------
    | Date the current model was last trained. Update this whenever
    | you retrain and re-run `python train_classifier.py`.
    |------------------------------------------------------------------
    */
    'last_trained' => '2026-09-15',
];