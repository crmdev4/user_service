<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;

class Account extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $keyType = 'string';
    public $incrementing = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($issue) {
            $issue->id = Str::uuid(36);
        });
    }

    protected $table = 'accounts';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account',
        'is_single_device',
        'is_banned',
    ];

    protected $hidden = [
        'created_user_id',
        'modified_user_id',
        'deleted_user_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "This model has been {$eventName}")
            ->useLogName('Account');
    }

    public function userAccounts(): HasMany
    {
        return $this->hasMany(UserAccount::class);
    }
}
