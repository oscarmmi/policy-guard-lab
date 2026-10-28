<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuardCheck extends Model
{
    protected $fillable = [
        'prompt',
        'verdict',
        'reasons',
        'rewritten_prompt',
        'answer',
        'is_example',
    ];

    protected function casts(): array
    {
        return [
            'reasons' => 'array',
            'is_example' => 'boolean',
        ];
    }
}
