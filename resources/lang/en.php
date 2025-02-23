<?php

return [
    'message' => [
        'default' => [
            'success' => 'Action process succeeded.',
            'failed' => 'Some error ocured. Process failed.',
            'post_too_large' => 'Size of attached file should be less :upload_max_filesize B.',
            'unauthenticated' => 'Unauthenticated or Token Expired, Please Login.',
            'too_many_request' => 'Too Many Requests, Please Slow Down.',
            'model_not_found' => 'Oops, Data for :model not found',
            'error_query' => 'There was Issue with the Query',
            'error' => 'There was some internal error',
            'unauthorized' => 'You are not authorized to access this feature.',
        ],
        'approval' => [
            'no_selected_workflow' => 'No workflow match with the document.',
            'first_step_not_init' => 'First step not init.',
            'data_has_been_closed' => 'Approval process failed, data has been closed.',
            'not_allowed' => 'You are not allowed to action approval process for this document.',
            'description_required' => 'Reason is mandatory',
        ],
    ]
];
