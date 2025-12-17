<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Photoshoot extends Model
{
    protected $fillable = [
        'shoot_name',
        'shoot_type',
        'date',
        'completed',
        'mood_board_url',
        'campaign_location',
        'campaign_models',
        'campaign_deliverables_video',
        'campaign_deliverables_photo',
        'campaign_deliverables_url',
        'studio_name',
        'studio_contact_name',
        'studio_phone_number',
        'studio_email',
        'studio_social_media',
        'studio_location',
        'studio_notes',
        'studio_spotlight_deliverables_video',
        'studio_spotlight_deliverables_photo_outside',
        'studio_spotlight_deliverables_photo_inside',
        'studio_spotlight_deliverables_url',
        'photographer',
        'graphic_designer',
        'scout_models',
        'model_outreach_communication',
        'styling',
        'order_return_props',
        'social_media_content',
        'concept_deck_call_sheet',
        'shoot_day_point_of_contact',
        'shoot_day_assistant',
        'bts_video_clips',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'completed' => 'boolean',
            'campaign_models' => 'array',
            'campaign_deliverables_video' => 'array',
            'campaign_deliverables_photo' => 'boolean',
            'studio_spotlight_deliverables_video' => 'array',
            'studio_spotlight_deliverables_photo_outside' => 'array',
            'studio_spotlight_deliverables_photo_inside' => 'array',
        ];
    }

    public function events()
    {
        return $this->hasMany(PhotoshootEvent::class)->orderBy('event_date', 'asc');
    }

    public function meetings()
    {
        return $this->hasMany(PhotoshootMeeting::class)->orderBy('meeting_date', 'asc');
    }

    // Team assignment relationships
    public function photographerUser()
    {
        return $this->belongsTo(User::class, 'photographer');
    }

    public function graphicDesignerUser()
    {
        return $this->belongsTo(User::class, 'graphic_designer');
    }

    public function scoutModelsUser()
    {
        return $this->belongsTo(User::class, 'scout_models');
    }

    public function modelOutreachUser()
    {
        return $this->belongsTo(User::class, 'model_outreach_communication');
    }

    public function stylingUser()
    {
        return $this->belongsTo(User::class, 'styling');
    }

    public function orderReturnPropsUser()
    {
        return $this->belongsTo(User::class, 'order_return_props');
    }

    public function socialMediaContentUser()
    {
        return $this->belongsTo(User::class, 'social_media_content');
    }

    public function conceptDeckCallSheetUser()
    {
        return $this->belongsTo(User::class, 'concept_deck_call_sheet');
    }

    public function shootDayPointOfContactUser()
    {
        return $this->belongsTo(User::class, 'shoot_day_point_of_contact');
    }

    public function shootDayAssistantUser()
    {
        return $this->belongsTo(User::class, 'shoot_day_assistant');
    }

    public function btsVideoClipsUser()
    {
        return $this->belongsTo(User::class, 'bts_video_clips');
    }
}
