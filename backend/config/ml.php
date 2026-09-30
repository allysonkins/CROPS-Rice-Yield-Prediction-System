<?php

return [
    // Python executable. On Windows XAMPP this is often just "python".
    // If it errors, use the full path, e.g. 'C:\Python311\python.exe'
    'python_binary' => env('ML_PYTHON_BINARY', 'python'),

    // Absolute path to the Flask ML service directory
    'service_dir'   => env('ML_SERVICE_DIR', base_path('../ml-service')),

    // Retrain script inside the service dir
    'retrain_script' => env('ML_RETRAIN_SCRIPT', base_path('../ml-service/retrain.py')),

    // Synthetic baseline CSV (already produced by generate_dataset.py)
    'synthetic_csv'  => env('ML_SYNTHETIC_CSV', base_path('../ml-service/rice_yield_dataset.csv')),

    // Where the retrain script writes versioned .pkl artifacts
    'artifacts_dir'  => env('ML_ARTIFACTS_DIR', base_path('../ml-service/artifacts')),

    // Pointer file read by the Flask service to hot-swap models
    'current_pointer' => env('ML_CURRENT_POINTER', base_path('../ml-service/current_model.json')),

    // Laravel-side scratch dirs
    'runs_dir'           => storage_path('app/ml/runs'),
    'training_data_dir'  => storage_path('app/ml/training_data'),

        /*
    |------------------------------------------------------------------
    | Retraining gates
    |
    | These control when the admin can click "Retrain Now".
    |   - min_samples_to_train : absolute minimum total harvested records
    |   - min_per_class        : minimum harvested records per class
    |                            (needed for the stratified train/test split)
    |   - cooldown_hours       : how long between successful retrains
    |------------------------------------------------------------------
    */
    'min_samples_to_train' => (int) env('ML_MIN_SAMPLES', 30),
    'min_per_class'        => (int) env('ML_MIN_PER_CLASS', 5),
    'cooldown_hours'       => (int) env('ML_COOLDOWN_HOURS', 6),
];