<?php

namespace App\Providers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Listeners\LogFailedLogin;
use App\Listeners\LogLogin;
use App\Observers\AuditObserver;
use App\Observers\BookObserver;
use App\Observers\CategoryObserver;
use App\Observers\OrderObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use App\Policies\BookPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ReviewPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Book::class => BookPolicy::class,
        Category::class => CategoryPolicy::class,
        Order::class => OrderPolicy::class,
        Review::class => ReviewPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Register model observers for automatic cache invalidation
        Category::observe(CategoryObserver::class);
        Book::observe(BookObserver::class);
        Order::observe(OrderObserver::class);

        Category::observe(AuditObserver::class);
        Book::observe(AuditObserver::class);
        User::observe(AuditObserver::class);

        Event::listen(Login::class, LogLogin::class);
        Event::listen(Failed::class, LogFailedLogin::class);
    }
}
