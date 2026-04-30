<?php

namespace App\Http\Controllers;

use App\Services\YmsoftErpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SitePageController extends Controller
{
    public function __construct(private readonly YmsoftErpClient $erp)
    {
    }

    public function home(): Response
    {
        $menus = $this->erp->get('web-profile/menu');
        $brands = $this->erp->get('web-profile/brands');
        $banners = $this->erp->get('web-profile/banners');
        $blocks = $this->erp->get('web-profile/home-blocks');
        $whatsOn = $this->extractWhatsOnItems($this->erp->get('mobile/member/whats-on'));

        return Inertia::render('Site/Home', [
            'menus' => $this->normalizeMenuLabels($menus),
            'brandLogos' => $this->normalizeBrandLogos($brands),
            'banner' => $banners[0] ?? null,
            'blocks' => array_values(array_filter($blocks, fn ($r) => is_array($r))),
            'news' => $whatsOn,
        ]);
    }

    public function brands(Request $request): Response
    {
        $nav = $this->baseNavData();
        $banners = $this->erp->get('web-profile/banners');
        $brandsPayload = $this->erp->get('mobile/member/brands');
        $list = is_array($brandsPayload['data'] ?? null) ? $brandsPayload['data'] : [];
        $brands = collect($list)->map(function ($item) {
            if (! is_array($item)) {
                return null;
            }
            $id = (int) ($item['id'] ?? 0);
            if ($id <= 0) {
                return $item;
            }
            $detail = $this->erp->get('mobile/member/brands/'.$id);
            $detailData = is_array($detail['data'] ?? null) ? $detail['data'] : null;
            return $detailData ?: $item;
        })->filter(fn ($row) => is_array($row))->values()->all();

        return Inertia::render('Site/Brands', [
            ...$nav,
            'heroImageUrl' => is_array($banners[0] ?? null) ? ($banners[0]['image'] ?? null) : null,
            'initialBrand' => (string) ($request->query('brand', '')),
            'brands' => $brands,
        ]);
    }

    public function careers(): Response
    {
        $nav = $this->baseNavData();
        $defaults = [
            'title' => 'CAREERS',
            'subtitle' => 'Growth Together with Justus Group',
            'hero_image_url' => null,
            'wording' => '',
            'cards' => [],
            'cta_title' => 'BE PART OF A JOURNEY TO CREATE THE FUTURE OF LIFESTYLE EXPERIENCES',
            'primary_button_label' => 'HEAD OFFICE Join Us',
            'primary_button_url' => '/careers/head-office',
            'secondary_button_label' => 'OPERATION Join Us',
            'secondary_button_url' => '/careers/outlet',
        ];
        $api = $this->erp->get('web-profile/careers-page');
        $page = is_array($api) && $api !== [] ? array_merge($defaults, $api) : $defaults;
        $page['primary_button_url'] = '/careers/head-office';
        $page['secondary_button_url'] = '/careers/outlet';

        return Inertia::render('Site/Careers', [
            ...$nav,
            'pageData' => $page,
        ]);
    }

    public function careersApply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'job_id' => 'required|integer',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:30',
            'cover_letter' => 'nullable|string',
            'cv_file' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $result = $this->erp->postJobVacancyApply(
            (int) $data['job_id'],
            [
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'cover_letter' => $data['cover_letter'] ?? '',
            ],
            $request->file('cv_file'),
        );

        if (($result['success'] ?? false) === true) {
            return response()->json([
                'message' => $result['message'] ?? 'Lamaran berhasil dikirim.',
            ]);
        }

        return response()->json([
            'message' => $result['message'] ?? 'Gagal kirim lamaran.',
        ], 422);
    }

    public function careersScope(string $scope): Response
    {
        $nav = $this->baseNavData();
        $scope = $scope === 'head-office' ? 'head_office' : 'outlet';
        $raw = $this->erp->getFromWeb('api/job-vacancies?scope='.$scope);
        $list = is_array($raw) ? $raw : [];
        $webBase = rtrim($this->erp->webBaseUrl(), '/');
        $vacancies = collect($list)
            ->map(function ($row) use ($webBase) {
                if (! is_array($row)) {
                    return null;
                }
                $banner = isset($row['banner']) && is_string($row['banner']) && $row['banner'] !== ''
                    ? $row['banner']
                    : null;
                if (empty($row['banner_url']) && $banner) {
                    $row['banner_url'] = $webBase.'/storage/'.$banner;
                }

                return $row;
            })
            ->filter(fn ($row) => is_array($row))
            ->values()
            ->all();

        return Inertia::render('Site/CareersVacancies', [
            ...$nav,
            'scope' => $scope,
            'vacancies' => $vacancies,
        ]);
    }

    public function whatsOn(): Response
    {
        $nav = $this->baseNavData();
        return Inertia::render('Site/WhatsOn', [
            ...$nav,
            'items' => $this->extractWhatsOnItems($this->erp->get('mobile/member/whats-on')),
        ]);
    }

    public function newsDetail(int $id): Response
    {
        $nav = $this->baseNavData();
        $items = $this->extractWhatsOnItems($this->erp->get('mobile/member/whats-on'));
        $item = collect($items)->first(fn ($row) => (int) ($row['id'] ?? 0) === $id);

        abort_if(! $item, 404);

        return Inertia::render('Site/NewsDetail', [
            ...$nav,
            'item' => $item,
        ]);
    }

    public function justusApps(): Response
    {
        $nav = $this->baseNavData();
        $page = $this->erp->get('web-profile/justus-apps-page');

        return Inertia::render('Site/JustusApps', [
            ...$nav,
            'pageData' => $page,
        ]);
    }

    public function homeService(): Response
    {
        $nav = $this->baseNavData();
        $payload = $this->erp->get('web-profile/home-service-packages');
        $packages = is_array($payload['packages'] ?? null) ? $payload['packages'] : [];

        return Inertia::render('Site/HomeService', [
            ...$nav,
            'heroImageUrl' => $payload['hero_image_url'] ?? null,
            'packages' => array_values(array_filter($packages, fn ($row) => is_array($row))),
        ]);
    }

    public function reservation(): Response
    {
        $nav = $this->baseNavData();
        $banners = $this->erp->get('web-profile/banners');
        $hero = is_array($banners[0] ?? null) ? ($banners[0]['image'] ?? null) : null;

        return Inertia::render('Site/Reservation', [
            ...$nav,
            'heroImageUrl' => $hero,
        ]);
    }

    public function reservationArrange(): Response
    {
        $nav = $this->baseNavData();
        $payload = $this->erp->get('mobile/member/brands');
        $list = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $outlets = collect($list)
            ->map(function ($b) {
                if (! is_array($b)) {
                    return null;
                }
                $id = (int) ($b['id'] ?? 0);
                if ($id <= 0) {
                    return null;
                }
                $gallery = is_array($b['gallery'] ?? null) ? $b['gallery'] : [];
                $firstImg = is_array($gallery[0] ?? null) ? ($gallery[0]['image'] ?? null) : null;
                $logo = isset($b['logo']) && is_string($b['logo']) ? $b['logo'] : null;

                return [
                    'id' => $id,
                    'name' => (string) ($b['name'] ?? ''),
                    'address' => $b['address'] ?? null,
                    'image' => $firstImg ?: $logo,
                ];
            })
            ->filter(fn ($row) => is_array($row))
            ->values()
            ->all();

        return Inertia::render('Site/ReservationArrange', [
            ...$nav,
            'outlets' => $outlets,
        ]);
    }

    public function reservationStatus(): Response
    {
        return Inertia::render('Site/ReservationStatus', $this->baseNavData());
    }

    public function about(): Response
    {
        $nav = $this->baseNavData();
        $fallback = [
            'title' => 'OUR STORY',
            'subtitle' => 'Elevating Culinary Experiences Since 2005',
            'hero_image_url' => null,
            'sections' => [
                [
                    'id' => 'our-story',
                    'title' => 'About Justus Group',
                    'subtitle' => null,
                    'content' => "Founded in 2005 and headquartered in Bandung, Indonesia, Justus Group is a dynamic restaurant company driven by a passion for quality and innovation.\nOperating under PT. Yuditama Mandiri, the group proudly presents signature brands with warm hospitality experiences.",
                    'image_url' => null,
                ],
                [
                    'id' => 'brand-philosophy',
                    'title' => 'Brand Philosophy',
                    'subtitle' => null,
                    'content' => "Connecting through cuisine. Enriching every life.\nAt Justus Group, we believe in culinary excellence to create memorable experiences and meaningful connections.",
                    'image_url' => null,
                ],
                [
                    'id' => 'vision-mission',
                    'title' => 'Vision & Mission',
                    'subtitle' => null,
                    'content' => "Vision:\nTo become the most preferred restaurant with warm caring hospitality experiences.\n\nMission:\nEngage every customer through excellent service and memorable experiences.",
                    'image_url' => null,
                ],
            ],
        ];

        $about = $this->erp->get('web-profile/about-page');
        $settings = $this->erp->get('web-profile/settings');
        $sections = collect(is_array($about['sections'] ?? null) ? $about['sections'] : [])
            ->map(function ($section, int $idx) use ($settings) {
                if (! is_array($section)) {
                    return null;
                }
                $id = (string) ($section['id'] ?? "s-{$idx}");
                $title = trim((string) ($section['title'] ?? '')) ?: "Section ".($idx + 1);
                $subtitle = trim((string) ($section['subtitle'] ?? ''));
                if ($id === 'vision-mission' && $subtitle === '') {
                    $subtitle = trim((string) ($settings['about_profile_role'] ?? ''));
                }
                $content = trim((string) ($section['content'] ?? ''));
                $imageUrl = $section['image_url'] ?? null;
                if ($content === '' && ! $imageUrl) {
                    return null;
                }
                return [
                    'id' => $id,
                    'title' => $title,
                    'subtitle' => $subtitle !== '' ? $subtitle : null,
                    'content' => $content,
                    'image_url' => $imageUrl,
                ];
            })
            ->filter(fn ($s) => is_array($s))
            ->values()
            ->all();

        return Inertia::render('Site/About', [
            ...$nav,
            'pageData' => [
                'title' => trim((string) ($about['title'] ?? '')) ?: $fallback['title'],
                'subtitle' => trim((string) ($about['subtitle'] ?? '')) ?: $fallback['subtitle'],
                'hero_image_url' => $about['hero_image_url'] ?? $fallback['hero_image_url'],
                'sections' => $sections !== [] ? $sections : $fallback['sections'],
            ],
        ]);
    }

    private function baseNavData(): array
    {
        $menus = $this->erp->get('web-profile/menu');
        $brands = $this->erp->get('web-profile/brands');

        return [
            'menus' => $this->normalizeMenuLabels($menus),
            'brandLogos' => $this->normalizeBrandLogos($brands),
        ];
    }

    private function normalizeMenuLabels(array $menus): array
    {
        $labels = collect($menus)
            ->map(fn ($item) => is_array($item) ? trim((string) ($item['label'] ?? '')) : '')
            ->filter()
            ->values()
            ->all();

        if ($labels === []) {
            return ['HOME', 'BRAND', 'HOME SERVICE', 'JUSTUS APPS', "WHAT'S ON", 'CAREERS', 'RESERVATION', 'ABOUT'];
        }

        if (! in_array('HOME', $labels, true)) {
            array_unshift($labels, 'HOME');
        }

        return $labels;
    }

    private function normalizeBrandLogos(array $brands): array
    {
        return collect($brands)->map(function ($brand) {
            if (! is_array($brand)) {
                return null;
            }

            return [
                'id' => (int) ($brand['id'] ?? 0),
                'title' => (string) ($brand['title'] ?? ''),
                'slug' => (string) ($brand['slug'] ?? ''),
                'logo' => (string) ($brand['logo_cp_url'] ?? $brand['thumbnail_url'] ?? $brand['image_url'] ?? ''),
            ];
        })->filter(fn ($row) => is_array($row) && $row['logo'] !== '')->values()->all();
    }

    private function extractWhatsOnItems(array $payload): array
    {
        $groups = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $flat = [];

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }
            $categoryName = (string) data_get($group, 'category.name', '');
            $items = is_array($group['items'] ?? null) ? $group['items'] : [];
            foreach ($items as $item) {
                if (! is_array($item) || ! isset($item['id'])) {
                    continue;
                }
                $flat[] = [
                    'id' => (int) $item['id'],
                    'title' => (string) ($item['title'] ?? "What's On"),
                    'content' => (string) ($item['content'] ?? ''),
                    'image' => $item['image'] ?? null,
                    'published_at' => $item['published_at'] ?? null,
                    'category_name' => $categoryName,
                ];
            }
        }

        return collect($flat)
            ->unique('id')
            ->sortByDesc(fn ($item) => (string) ($item['published_at'] ?? ''))
            ->values()
            ->all();
    }
}

