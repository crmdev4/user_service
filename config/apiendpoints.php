<?php

return [
    'LOCAL_API_EMPLOYEES' => env('LOCAL_API_EMPLOYEES', 'http://localhost.employees:5555'),
    'LOCAL_API_EMPLOYEES_KEY' => env('LOCAL_API_EMPLOYEES_KEY', 'xN9P6a8sL2bV3iR4fC5J6Q7kT8yU9wZ0'),

    'LOCAL_API_COMPANIES' => env('LOCAL_API_COMPANIES', 'http://localhost.companies:6666'),
    'LOCAL_API_COMPANIES_KEY' => env('LOCAL_API_COMPANIES_KEY', 'xN9P6a8sL2bV3iR4fC5J6Q7kT8yU9wZ0'),

    'LOCAL_API_ATTENDANCES' => env('LOCAL_API_ATTENDANCES', 'http://localhost.attendance:8888'),
    'LOCAL_API_ATTENDANCES_KEY' => env('LOCAL_API_ATTENDANCES_KEY', 'xN9P6a8sL2bV3iR4fC5J6Q7kT8yU9wZ0'),

    'LOCAL_API_PAYROLL' => env('LOCAL_API_PAYROLL', 'http://localhost.payroll:7777'),
    'LOCAL_API_PAYROLL_KEY' => env('LOCAL_API_PAYROLL_KEY', 'xN9P6a8sL2bV3iR4fC5J6Q7kT8yU9wZ0'),

    'LOCAL_API_NOTIFICATION' => env('LOCAL_API_NOTIFICATION', 'http://nginx_notifications_services:80'),

    'DEV_S3_URL' => env('DEV_S3_URL', 'https://apis3.rentfms.com'),
    'PRODUCTION_S3_URL' => env('PRODUCTION_S3_URL', 'https://apis3.rentfms.com'),

    'AUTH_API_URL' => env('API_AUTH_URL', 'http://localhost.auth:9999'),
    'FRONTEND_URL' => env('FRONTEND_URL', 'http://localhost:3000'),
];
