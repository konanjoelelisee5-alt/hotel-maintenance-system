<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistTemplateItem extends Model
{
    protected $fillable = ['checklist_template_id', 'label', 'position'];

    public function template()
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }
}