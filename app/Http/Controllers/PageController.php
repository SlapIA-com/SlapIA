<?php

namespace App\Http\Controllers;

use App\Services\ReviewsService;
use App\Services\StatsService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pages marketing statiques (contenu 100% piloté par les traductions —
 * voir lang/fr/messages.php, partagé au front via HandleInertiaRequests).
 */
class PageController extends Controller
{
    public function home(StatsService $stats, ReviewsService $reviews): Response
    {
        return Inertia::render('Home', [
            'stats' => $stats->get(),
            'reviews' => $reviews->getPublicReviews(12),
        ]);
    }

    public function courses(): Response
    {
        return Inertia::render('Courses');
    }

    public function services(): Response
    {
        return Inertia::render('Services');
    }

    public function pricing(): Response
    {
        return Inertia::render('Pricing');
    }

    public function about(StatsService $stats): Response
    {
        return Inertia::render('About', [
            'stats' => $stats->get(),
        ]);
    }

    public function legalMentions(): Response
    {
        return Inertia::render('Legal/Mentions');
    }

    public function legalPrivacy(): Response
    {
        return Inertia::render('Legal/Privacy');
    }

    public function legalCgv(): Response
    {
        return Inertia::render('Legal/Cgv');
    }
}
