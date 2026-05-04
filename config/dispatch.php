<?php

use App\Support\DispatchBm03TargetOptions;

return [
    /*
    |--------------------------------------------------------------------------
    | BM.03 target checkboxes (key => label)
    |--------------------------------------------------------------------------
    | Keys submitted by the wizard match labels in dispatchWizardConstants.TARGET_OPTIONS.
    */
    'target_options' => collect(DispatchBm03TargetOptions::OPTIONS)
        ->mapWithKeys(fn (string $label) => [$label => $label])
        ->all(),
];
