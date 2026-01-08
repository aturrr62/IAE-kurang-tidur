<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalApiKey extends Model
{
    use HasFactory;

    protected $table = 'external_api_keys';

    protected $fillable = ['client_name', 'api_key', 'secret_key', 'is_active', 'expires_at'];
}
