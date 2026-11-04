<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Table `catalog_stock` (see database/migrations). */
class CatalogStock extends Model
{
    protected $table = 'catalog_stock';
    protected $primaryKey = 'json_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}
