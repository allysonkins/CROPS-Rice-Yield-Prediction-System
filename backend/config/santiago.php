<?php

return [
    'barangays' => [
        'Abra','Ambalatungan','Balintocatoc','Baluarte','Bannawag Norte','Batal',
        'Buenavista','Cabulay','Calao East','Calao West','Calaocan','Centro East',
        'Centro West','Divisoria','Dubinan East','Dubinan West','Luna','Mabini',
        'Malvar','Nabbuan','Naggasican','Patul','Plaridel','Rizal','Rosario',
        'Sagana','Salvador','San Andres','San Isidro','San Jose','Santa Rosa',
        'Sinili','Sinsayon','Victory Norte','Victory Sur','Villa Gonzaga','Villasis',
    ],

    'centroids' => [
        'Abra'            => ['lat' => 16.6850, 'lng' => 121.5500],
        'Ambalatungan'    => ['lat' => 16.6900, 'lng' => 121.5520],
        'Balintocatoc'    => ['lat' => 16.6840, 'lng' => 121.5470],
        'Baluarte'        => ['lat' => 16.6920, 'lng' => 121.5490],
        'Bannawag Norte'  => ['lat' => 16.6830, 'lng' => 121.5520],
        'Batal'           => ['lat' => 16.6860, 'lng' => 121.5540],
        'Buenavista'      => ['lat' => 16.6895, 'lng' => 121.5510],
        'Cabulay'         => ['lat' => 16.6825, 'lng' => 121.5485],
        'Calao East'      => ['lat' => 16.6935, 'lng' => 121.5535],
        'Calao West'      => ['lat' => 16.6810, 'lng' => 121.5455],
        'Calaocan'        => ['lat' => 16.6880, 'lng' => 121.5480],
        'Centro East'     => ['lat' => 16.6870, 'lng' => 121.5480],
        'Centro West'     => ['lat' => 16.6885, 'lng' => 121.5465],
        'Divisoria'       => ['lat' => 16.6875, 'lng' => 121.5430],
        'Dubinan East'    => ['lat' => 16.6800, 'lng' => 121.5500],
        'Dubinan West'    => ['lat' => 16.6960, 'lng' => 121.5520],
        'Luna'            => ['lat' => 16.6790, 'lng' => 121.5475],
        'Mabini'          => ['lat' => 16.6890, 'lng' => 121.5420],
        'Malvar'          => ['lat' => 16.6950, 'lng' => 121.5550],
        'Nabbuan'         => ['lat' => 16.6865, 'lng' => 121.5580],
        'Naggasican'      => ['lat' => 16.6820, 'lng' => 121.5530],
        'Patul'           => ['lat' => 16.6980, 'lng' => 121.5510],
        'Plaridel'        => ['lat' => 16.6785, 'lng' => 121.5525],
        'Rizal'           => ['lat' => 16.6905, 'lng' => 121.5425],
        'Rosario'         => ['lat' => 16.6870, 'lng' => 121.5600],
        'Sagana'          => ['lat' => 16.6682, 'lng' => 121.5498],
        'Salvador'        => ['lat' => 16.6890, 'lng' => 121.5560],
        'San Andres'      => ['lat' => 16.6523, 'lng' => 121.5620],
        'San Isidro'      => ['lat' => 16.7189, 'lng' => 121.5520],
        'San Jose'        => ['lat' => 16.7069, 'lng' => 121.5714],
        'Santa Rosa'      => ['lat' => 16.7123, 'lng' => 121.5750],
        'Sinili'          => ['lat' => 16.6820, 'lng' => 121.5580],
        'Sinsayon'        => ['lat' => 16.6840, 'lng' => 121.5600],
        'Victory Norte'   => ['lat' => 16.6950, 'lng' => 121.5600],
        'Victory Sur'     => ['lat' => 16.6900, 'lng' => 121.5620],
        'Villa Gonzaga'   => ['lat' => 16.6840, 'lng' => 121.5560],
        'Villasis'        => ['lat' => 16.6880, 'lng' => 121.5580],
    ],

    /*
    |--------------------------------------------------------------------------
    | ML-Trained Rice Varieties
    |--------------------------------------------------------------------------
    |
    | The exact variety names the Random Forest / XGBoost model was trained on.
    | Sourced from ml-service/feature_legend.json (the variety_* one-hot keys).
    |
    | Update this list whenever you retrain the model with new varieties.
    | The RiceVarietyController matches DB names against this list (loosely —
    | spaces, underscores, dashes, and case are ignored) to decide whether
    | a variety card shows "In ML Model" or "Fallback Mode".
    |
    */

    'ml_trained_varieties' => [
        'Angelica (NSIC Rc122)',
        'NSIC 2016 Rc 456H (Mestiso 78)',
        'NSIC RC 402 (Tubigan 36)',
        'NSIC Rc 480',
        'NSIC Rc 486 (Mestiso 80)',
        'NSIC Rc 512 (Tubigan 44)',
        'NSIC Rc 534 (Salinas 29)',
        'NSIC Rc 666H',
        'NSIC Rc124H (MESTISO 4)',
        'NSIC Rc132H (MESTISO 6)',
        'NSIC Rc160 (Tubigan 14)',
        'NSIC Rc204H (Mestiso 20)',
        'NSIC Rc216 (Tubigan 17)',
        'NSIC Rc222 (Tubigan 18)',
        'NSIC Rc234H (MESTISO 27)',
        'NSIC Rc440(Tubigan 39)',
        'PSB Rc18 (Ala)',
        'PSB Rc72H (Mestiso)',
    ],
];