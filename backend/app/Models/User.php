<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_TEAM_MEMBER = 'team_member';
    public const ROLE_CLIENT = 'client';

    public static function roles(): array
    {
        return [
            self::ROLE_OWNER,
            self::ROLE_ADMIN,
            self::ROLE_TEAM_MEMBER,
            self::ROLE_CLIENT,
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function company()
    {
        return $this->hasOne(Company::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function projectFiles()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function issues()
    {
        return $this->hasMany(Issue::class);
    }

    public function analyticsEvents()
    {
        return $this->hasMany(AnalyticsEvent::class);
    }

    public function impersonationEventsAsImpersonator()
    {
        return $this->hasMany(ImpersonationEvent::class, 'impersonator_user_id');
    }

    public function impersonationEventsAsTarget()
    {
        return $this->hasMany(ImpersonationEvent::class, 'target_user_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('current_period_ends_at')
                    ->orWhere('current_period_ends_at', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->latestOfMany();
    }

    public function latestPlan(): ?Plan
    {
        return $this->activeSubscription?->plan;
    }

    public function currentPlan(): ?Plan
    {
        $subscription = $this->relationLoaded('activeSubscription')
            ? $this->getRelation('activeSubscription')
            : $this->activeSubscription()->with('plan')->first();

        if ($subscription?->plan) {
            return $subscription->plan;
        }

        if ($this->trial_ends_at && $this->trial_ends_at->isFuture()) {
            return Plan::query()->where('code', 'starter')->first();
        }

        return null;
    }

    public function hasFeature(string $feature): bool
    {
        $plan = $this->currentPlan();

        return in_array($feature, $plan?->features ?? [], true);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'google_id',
        'email_verification_token',
        'email_verification_sent_at',
        'trial_ends_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
        'email_verification_token',
        'email_verification_sent_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_sent_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
