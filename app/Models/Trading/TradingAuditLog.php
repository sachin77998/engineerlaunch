<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** Audit trail of every trading action. */
class TradingAuditLog extends Model
{
    protected $table = 'trading_audit_logs';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];
}
