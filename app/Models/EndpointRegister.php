<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;

class EndpointRegister extends Model
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

    protected $table = 'endpoint_register';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'permission_id',
        'service_name',
        'base_uri',
        'version',
        'method',
        'path',
        'api_key',
        'status',
        'reference',
    ];

    protected $hidden = [
         
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "This model has been {$eventName}")
            ->useLogName('Endpoint Register');
    }
}
