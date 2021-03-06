<?php namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @codeCoverageIgnore
 * NOTE until needed ignored since not special work in here
 */
class Project extends Model
{

    protected $fillable = ['title', 'body', 'created_at', 'updated_at', 'photo_file_name', 'rendered_body'];
    
    public static $rules = [];

    public function tags()
    {
        return $this->belongsToMany(\App\Tag::class);
    }


    public static function boot()
    {
        parent::boot();
        Project::observe(new ProjectsObserver());
    }
}
