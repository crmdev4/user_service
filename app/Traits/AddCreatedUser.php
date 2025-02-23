<?php

namespace App\Traits;

use Exception;

trait AddCreatedUser
{
    protected static function bootAddCreatedUser()
    {
        static::creating(function ($model) {
            try {
                $authUser = auth()->user();

                if ($authUser != null && is_object($authUser)) {
                    $model->created_user_id = $authUser->id;
                    $model->modified_user_id = $authUser->id;
                }
            } catch (Exception $e) {
                abort(500, $e->getMessage());
            }
        });
    }
}
