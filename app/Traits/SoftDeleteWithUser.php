<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;

trait SoftDeleteWithUser
{
    use SoftDeletes {
        SoftDeletes::runSoftDelete as parentRunSoftDelete;
        SoftDeletes::restore as parentRestore;
    }

    public function runSoftDelete()
    {
        $this->parentRunSoftDelete();
        $query = $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey());

        $authUser = auth()->user();

        if ($authUser != null && is_object($authUser)) {
            $columns = ['deleted_user_id' => $authUser->id];
            $this->deleted_user_id = $authUser->id;
        } else {
            $columns = ['deleted_user' => "1"];
            $this->deleted_user = "1";
        }

        $query->update($columns);
    }

    public function restore()
    {
        $result = $this->parentRestore();
        $this->deleted_user_id = null;
        $this->save();
        return $result;
    }
}
