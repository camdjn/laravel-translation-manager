<?php namespace camdjn\TranslationManager\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Group model
 *
 * @property string $id
 * @property string  $label
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Locale extends Model{

    protected $table = 'stm_locales';
    protected $guarded = array('created_at', 'updated_at');
    protected $fillable = ['label'];

}
