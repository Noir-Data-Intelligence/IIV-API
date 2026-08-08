<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\HeroSlide\HeroSlideRequest;
use App\Http\Resources\HeroSlideResource;
use App\Models\HeroSlide;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class HeroSlideController extends Controller
{
    use PaginatesRequests;

    /** GET /hero-slides -> so publicados, ordenados por sortOrder (PUBLICO). */
    public function index(): JsonResponse
    {
        $slides = HeroSlide::query()->where('published', true)->orderBy('sort_order')->get();

        return response()->json(HeroSlideResource::collection($slides)->resolve());
    }

    /** GET /hero-slides/admin -> todos, paginado + filtrado (autenticado). */
    public function adminIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = HeroSlide::query()->orderBy('sort_order');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('kicker', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%");
            });
        }

        return PaginatedResponse::make($query->paginate($perPage), HeroSlideResource::class);
    }

    public function store(HeroSlideRequest $request): JsonResponse
    {
        $data = $request->validated();
        $maxOrder = (int) HeroSlide::query()->max('sort_order');

        $slide = HeroSlide::query()->create([
            'id' => (string) Str::uuid(),
            'kicker' => $data['kicker'],
            'title' => $data['title'],
            'subtitle' => $data['subtitle'],
            'cta_label' => $data['ctaLabel'],
            'cta_link' => $data['ctaLink'],
            'image_url' => $data['imageUrl'] ?? null,
            'sort_order' => $data['sortOrder'] ?? $maxOrder + 1,
            'published' => $data['published'] ?? true,
        ]);

        return (new HeroSlideResource($slide))->response()->setStatusCode(201);
    }

    public function update(HeroSlideRequest $request, HeroSlide $heroSlide): HeroSlideResource
    {
        $data = $request->validated();

        $heroSlide->update([
            'kicker' => $data['kicker'],
            'title' => $data['title'],
            'subtitle' => $data['subtitle'],
            'cta_label' => $data['ctaLabel'],
            'cta_link' => $data['ctaLink'],
            'image_url' => $data['imageUrl'] ?? null,
            'sort_order' => $data['sortOrder'] ?? $heroSlide->sort_order,
            'published' => $data['published'] ?? $heroSlide->published,
        ]);

        return new HeroSlideResource($heroSlide);
    }

    public function destroy(HeroSlide $heroSlide): Response
    {
        $heroSlide->delete();

        return response()->noContent();
    }
}
