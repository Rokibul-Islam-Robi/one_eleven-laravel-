<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `schema_migrations` (see database/migrations). */
class SchemaMigration extends Model
{
    protected $table = 'schema_migrations';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
        ];
    }
}
