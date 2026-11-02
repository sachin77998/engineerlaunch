<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialFeedback extends Model
{
    public const KINDS = ['question' => 'Question about a product', 'issue' => 'Problem with an order', 'delivery' => 'Delivery', 'suggestion' => 'Suggestion'];

    protected $table = 'material_feedback';
    protected $guarded = [];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MaterialOrder::class, 'material_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
