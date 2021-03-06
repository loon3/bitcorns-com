<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id', 'tx_id', 'type', 'address', 'name', 'description', 'image_url', 'rewards_total', 'latitude', 'longitude', 'processed_at',
    ];

    /**
     * The attributes that are dates.
     *
     * @var array
     */
    protected $dates = [
        'processed_at',
    ];

    /**
     * These attributes are dynamically added.
     *
     * @var array
     */
    protected $appends = [
        'display_name', 'display_image_url', 'map_radius', 'url',
    ];

    /**
     * Display Name
     *
     * @var string
     */
    public function getDisplayNameAttribute()
    {
        return $this->accessBalance()->quantity ? $this->name : 'NO CROPPER';
    }

    /**
     * Display Image Url
     *
     * @var string
     */
    public function getDisplayImageUrlAttribute()
    {
        return $this->accessBalance()->quantity ? $this->image_url : env('NO_ACCESS_IMAGE_URL');
    }

    /**
     * Map Radius
     *
     * @var string
     */
    public function getMapRadiusAttribute()
    {
        if(! $this->accessBalance()) return 0;

        $acres = $this->accessBalance()->display_quantity / 0.00003810;
        $area = $meters_squared = $acres * 4046.85642;
        $radius = sqrt($area / pi());

        return $radius;
    }

    /**
     * Url
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return route('players.show', ['player' => $this->address]);
    }

    /**
     * Achievements
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function achievements()
    {
        return $this->belongsToMany(Achievement::class)->withPivot('meta')->withTimestamps();
    }

    /**
     * Balances
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function balances()
    {
        return $this->hasMany(Balance::class)->with('token');
    }

    /**
     * Memberships
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Rewards
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function rewards()
    {
        return $this->belongsToMany(Reward::class)->withPivot('total');
    }

    /**
     * Tokens
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tokens()
    {
        return $this->belongsToMany(Token::class, 'balances');
    }

    /**
     * Tx
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tx()
    {
        return $this->belongsTo(Tx::class);
    }

    /**
     * Txs
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function txs()
    {
        return $this->hasMany(Tx::class, 'source', 'address');
    }

    /**
     * Uploads
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function uploads()
    {
        return $this->hasMany(Upload::class);
    }

    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    public function scopeWhereHasAccess($query)
    {
        $token = \App\Token::whereType('access')->first();
        return $query->whereHas('balances', function ($query) use ($token) {
            $query->whereTokenId($token->id)->where('quantity', '>', 0);
        })->processed()->withCount('rewards');
    }

    public function scopeWhereHasNoAccess($query)
    {
        $token = \App\Token::whereType('access')->first();
        return $query->whereHas('balances', function ($query) use ($token) {
            $query->whereTokenId($token->id)->whereQuantity(0);
        })->processed()->withCount('rewards');
    }

    /**
     * Access Balance
     *
     * @return \App\Balance
     */
    public function accessBalance()
    {
        return $this->getBalance(env('ACCESS_TOKEN_NAME'));
    }

    /**
     * Reward Balance
     *
     * @return \App\Balance
     */
    public function rewardBalance()
    {
        return $this->getBalance(env('REWARD_TOKEN_NAME'));
    }

    /**
     * Get Any Balance
     *
     * @return \App\Balance
     */
    public function getBalance($token_name)
    {
        return $this->balances()->with(['token' => function ($query) use ($token_name) {
            $query->where('name', '=', $token_name);
        }])->firstOrFail();
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'address';
    }
}