<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'long_name', 'issuer', 'content', 'description', 'image_url', 'thumb_url', 'total_issued', 'divisible', 'locked',
    ];

    /**
     * The attributes that are appended.
     *
     * @var array
     */
    protected $appends = [
        'display_name', 'display_total', 'display_image_url', 'display_thumb_url',
        'url', 'edit_url', 'explorer_url',
    ];

    /**
     * Display Name
     *
     * @return string
     */
    public function getDisplayNameAttribute()
    {
        return $this->long_name ? $this->long_name : $this->name;
    }

    /**
     * Display Total
     *
     * @return string
     */
    public function getDisplayTotalAttribute()
    {
        return $this->divisible ? fromSatoshi($this->total_issued) : number_format($this->total_issued);
    }

    /**
     * Display Image Url
     *
     * @return string
     */
    public function getDisplayImageUrlAttribute()
    {
        return $this->image_url ? $this->image_url : env('DEFAULT_TOKEN_IMAGE');
    }

    /**
     * Display Thumb Url
     *
     * @return string
     */
    public function getDisplayThumbUrlAttribute()
    {
        return $this->thumb_url ? $this->thumb_url : env('DEFAULT_TOKEN_THUMB');
    }

    /**
     * Url
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return route('tokens.show', ['token' => $this->name]);
    }

    /**
     * Edit Url
     *
     * @return string
     */
    public function getEditUrlAttribute()
    {
        return route('tokens.edit', ['token' => $this->name]);
    }

    /**
     * Explorer Url
     *
     * @return string
     */
    public function getExplorerUrlAttribute()
    {
        return 'https://xchain.io/asset/' . $this->name;
    }

    /**
     * Balances
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function balances()
    {
        return $this->hasMany(Balance::class)->with('player');
    }

    /**
     * Players
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function players()
    {
        return $this->belongsToMany(Player::class, 'balances')->withPivot('quantity');
    }

    /**
     * Rewards
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rewards()
    {
        return $this->hasMany(Reward::class);
    }

    /**
     * Txs
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function txs()
    {
        return $this->hasMany(Tx::class);
    }

    /**
     * Enforce Type Limit
     */
    public static function boot() {
        static::creating(function (Token $token) {
            if(in_array($token->type, ['access', 'reward']) && Token::whereType($token->type)->exists()) {
                throw new \Exception('Token Limit Exceeded');
            }
        });
        parent::boot();
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'name';
    }
}