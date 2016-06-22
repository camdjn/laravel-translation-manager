<?php namespace camdjn\TranslationManager\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Translation model
 *
 * @property integer $id
 * @property string  $group_id
 * @property integer $status
 * @property string  $locale
 * @property string  $key
 * @property string  $value
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Translation extends Model{

    const STATUS_SAVED = 0;
    const STATUS_CHANGED = 1;

    protected $table = 'stm_translations';
    protected $guarded = array('id', 'created_at', 'updated_at');
    protected $fillable = array('group_id', 'locale_id', 'key');

    public function group()
    {
    	return $this->belongsTo('camdjn\TranslationManager\Models\Group');
    }

    public function locale()
    {
    	return $this->hasOne('camdjn\TranslationManager\Models\Locale');
    }

}
