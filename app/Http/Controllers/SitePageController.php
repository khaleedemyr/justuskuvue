<?php

namespace App\Http\Controllers;

use App\Services\YmsoftErpClient;
use App\Support\SiteMediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

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
        $promoSlides = $this->erp->get('web-profile/promo-slides');
        $blocks = $this->erp->get('web-profile/home-blocks');
        $whatsOn = $this->extractWhatsOnItems($this->erp->get('mobile/member/whats-on'));

        $banner = $this->enrichBannerMedia($banners[0] ?? null);
        $this->shareHomeLcpShell($banner);

        return Inertia::render('Site/Home', [
            'menus' => $this->normalizeMenuLabels($menus),
            'brandLogos' => $this->normalizeBrandLogos($brands),
            'banner' => $banner,
            'promoSlides' => array_values(array_filter(is_array($promoSlides) ? $promoSlides : [], fn ($r) => is_array($r))),
            'blocks' => array_values(array_filter($blocks, fn ($r) => is_array($r))),
            'news' => $whatsOn,
        ]);
    }

    public function brands(Request $request): Response
    {
        $nav = $this->baseNavData();
        $banners = $this->erp->get('web-profile/banners');
        $brandsPayload = $this->erp->get('mobile/member/brands', ['include_fc' => 1]);
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

        $landingSlugs = [];
        $landingsIndex = $this->erp->getFresh('web-profile/outlet-landings');
        if (is_array($landingsIndex)) {
            foreach ($landingsIndex as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $outletId = (int) ($row['outlet_id'] ?? 0);
                $slug = (string) ($row['slug'] ?? '');
                if ($outletId > 0 && $slug !== '') {
                    $landingSlugs[(string) $outletId] = $slug;
                }
            }
        }

        return Inertia::render('Site/Brands', [
            ...$nav,
            'heroImageUrl' => is_array($banners[0] ?? null) ? ($banners[0]['image'] ?? null) : null,
            'initialBrand' => (string) ($request->query('brand', '')),
            'brands' => $brands,
            'landingSlugs' => $landingSlugs,
        ]);
    }

    public function outletLanding(Request $request, string $slug): Response
    {
        $query = [];
        if ($request->filled('preview')) {
            $query['preview'] = (string) $request->query('preview');
        }

        $payload = $this->erp->getFresh('web-profile/outlet-landings/'.$slug, $query);
        if (! is_array($payload) || empty($payload['slug'])) {
            abort(404);
        }

        return Inertia::render('Site/OutletLanding', [
            ...$this->baseNavData(),
            'landing' => $payload,
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
            'cta_subtitle' => '',
            'cta_image_1_url' => null,
            'cta_image_2_url' => null,
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
            'domicile' => 'required|string|max:255',
            'last_education' => 'required|string|max:255',
            'birth_date' => 'required|date|before:today',
            'cover_letter' => 'nullable|string',
            'cv_file' => 'required|file|mimes:pdf,doc,docx|max:5120',
            'photo_file' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            'recaptcha_token' => 'required|string|max:4096',
            'form_started_at' => 'required|integer',
            'company_website' => 'nullable|string|max:255',
        ]);
        if (trim((string) ($data['company_website'] ?? '')) !== '') {
            Log::warning('Career apply honeypot triggered', ['ip' => $request->ip()]);
            return response()->json([
                'message' => 'Permintaan tidak valid.',
            ], 422);
        }
        if ((time() - (int) $data['form_started_at']) < 3) {
            Log::warning('Career apply too-fast submit', ['ip' => $request->ip()]);
            return response()->json([
                'message' => 'Permintaan terlalu cepat.',
            ], 422);
        }

        $captchaStatus = $this->verifyRecaptcha((string) $data['recaptcha_token'], (string) $request->ip(), 'career_apply');
        Log::info('Career apply captcha evaluated', [
            'ip' => $request->ip(),
            'result' => $captchaStatus,
            'email_hash' => sha1(strtolower(trim((string) $data['email']))),
        ]);
        if (! $captchaStatus['ok']) {
            return response()->json([
                'message' => 'Captcha verification failed. Please try again.',
            ], 422);
        }
        if (($captchaStatus['risk_level'] ?? 'low') !== 'low') {
            Log::warning('Career apply medium-risk captcha pass', [
                'ip' => $request->ip(),
                'result' => $captchaStatus,
                'email_hash' => sha1(strtolower(trim((string) $data['email']))),
            ]);
        }

        $result = $this->erp->postJobVacancyApply(
            (int) $data['job_id'],
            [
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'domicile' => $data['domicile'],
                'last_education' => $data['last_education'],
                'birth_date' => $data['birth_date'],
                'cover_letter' => $data['cover_letter'] ?? '',
            ],
            $request->file('cv_file'),
            $request->file('photo_file'),
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

    private function verifyRecaptcha(string $token, string $ip, string $expectedAction): array
    {
        $secret = (string) config('services.recaptcha.secret_key', '');
        $hardBlockScore = (float) config('services.recaptcha.hard_block_score', 0.5);
        $mediumRiskScore = (float) config('services.recaptcha.medium_risk_score', 0.7);
        if ($mediumRiskScore < $hardBlockScore) {
            $mediumRiskScore = $hardBlockScore;
        }
        if ($secret === '' || $token === '') {
            return ['ok' => false, 'reason' => 'missing_secret_or_token'];
        }

        try {
            $res = Http::asForm()->timeout(10)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);
            $json = $res->json();
            if (! (bool) ($json['success'] ?? false)) {
                return ['ok' => false, 'reason' => 'google_unsuccess', 'error_codes' => $json['error-codes'] ?? []];
            }
            $action = (string) ($json['action'] ?? '');
            $score = (float) ($json['score'] ?? 0);
            if ($action !== '' && $action !== $expectedAction) {
                return ['ok' => false, 'reason' => 'action_mismatch', 'action' => $action, 'score' => $score];
            }
            if ($score < $hardBlockScore) {
                return ['ok' => false, 'reason' => 'low_score', 'action' => $action, 'score' => $score];
            }
            if ($score < $mediumRiskScore) {
                return ['ok' => true, 'reason' => 'passed_medium_risk', 'action' => $action, 'score' => $score, 'risk_level' => 'medium'];
            }
            return ['ok' => true, 'reason' => 'passed', 'action' => $action, 'score' => $score, 'risk_level' => 'low'];
        } catch (Throwable) {
            return ['ok' => false, 'reason' => 'verify_exception'];
        }
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
        if (is_array($page)) {
            $page['playstore_url'] = $this->sanitizeCmsExternalUrl($page['playstore_url'] ?? null);
            $page['appstore_url'] = $this->sanitizeCmsExternalUrl($page['appstore_url'] ?? null);
        }

        return Inertia::render('Site/JustusApps', [
            ...$nav,
            'pageData' => $page,
        ]);
    }

    public function homeService(): Response
    {
        $nav = $this->baseNavData();
        $payload = $this->erp->get('web-profile/home-service-packages');
        $landing = is_array($payload['landing'] ?? null) ? $payload['landing'] : [];
        $landing = $this->sanitizeHomeServiceLanding($landing);

        return Inertia::render('Site/HomeService', [
            ...$nav,
            'heroImageUrl' => $payload['hero_image_url'] ?? null,
            'heroTitle' => $payload['hero_title'] ?? null,
            'heroSubtitle' => $payload['hero_subtitle'] ?? null,
            'landing' => $landing,
        ]);
    }

    public function homeServiceMenu(): Response
    {
        $nav = $this->baseNavData();
        $payload = $this->erp->get('web-profile/home-service-packages');
        $packages = is_array($payload['packages'] ?? null) ? $payload['packages'] : [];

        return Inertia::render('Site/HomeServiceMenu', [
            ...$nav,
            'heroImageUrl' => $payload['hero_image_url'] ?? null,
            'heroTitle' => $payload['hero_title'] ?? null,
            'heroSubtitle' => $payload['hero_subtitle'] ?? null,
            'packages' => array_values(array_filter($packages, fn ($row) => is_array($row))),
        ]);
    }

    public function reservation(): Response
    {
        if ($this->isReservationMaintenanceEnabled()) {
            return $this->reservationMaintenanceResponse();
        }

        $nav = $this->baseNavData();
        $banners = $this->erp->get('web-profile/banners');
        $bannerList = collect(is_array($banners) ? $banners : [])
            ->filter(fn ($row) => is_array($row))
            ->values();

        $reservationBanner = $bannerList->first(function ($row) {
            $title = strtolower(trim((string) ($row['title'] ?? '')));
            $subtitle = strtolower(trim((string) ($row['subtitle'] ?? '')));
            $haystack = $title.' '.$subtitle;

            return str_contains($haystack, 'reservation') || str_contains($haystack, 'reservasi');
        });

        $hero = is_array($reservationBanner)
            ? ($reservationBanner['image'] ?? null)
            : (is_array($bannerList->first()) ? ($bannerList->first()['image'] ?? null) : null);

        return Inertia::render('Site/Reservation', [
            ...$nav,
            'heroImageUrl' => $hero,
        ]);
    }

    public function reservationArrange(Request $request): Response
    {
        if ($this->isReservationMaintenanceEnabled()) {
            return $this->reservationMaintenanceResponse();
        }

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
            'initialOutletId' => (string) ($request->query('outlet_id', '')),
        ]);
    }

    public function reservationStatus(): Response
    {
        if ($this->isReservationMaintenanceEnabled()) {
            return $this->reservationMaintenanceResponse();
        }

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

    private function isReservationMaintenanceEnabled(): bool
    {
        return (bool) config('services.ymsofterp.reservation_maintenance_enabled', false);
    }

    private function reservationMaintenanceResponse(): Response
    {
        return Inertia::render('Site/ReservationMaintenance', [
            ...$this->baseNavData(),
            'heroImageUrl' => null,
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
                'hero_title' => (string) ($brand['hero_title'] ?? ''),
                'hero_subtitle' => (string) ($brand['hero_subtitle'] ?? ''),
                'hero_media_url' => (string) ($brand['hero_media_url'] ?? ''),
                'hero_media_type' => (string) ($brand['hero_media_type'] ?? ''),
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

    /**
     * @param  array<string, mixed>  $landing
     * @return array<string, mixed>
     */
    private function sanitizeHomeServiceLanding(array $landing): array
    {
        foreach (['gallery_card', 'menu_card', 'cta'] as $nodeKey) {
            if (! isset($landing[$nodeKey]) || ! is_array($landing[$nodeKey])) {
                continue;
            }
            $safeUrl = $this->sanitizeCmsExternalUrl($landing[$nodeKey]['url'] ?? null);
            $landing[$nodeKey]['url'] = $safeUrl;
        }

        return $landing;
    }

    private function sanitizeCmsExternalUrl(mixed $url): ?string
    {
        $raw = trim((string) ($url ?? ''));
        if ($raw === '') {
            return null;
        }

        // Relative URL tetap diizinkan (internal navigation).
        if (str_starts_with($raw, '/')) {
            return $raw;
        }

        if (! filter_var($raw, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($raw);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return null;
        }

        $allowHttpOnLocal = app()->environment('local');
        if (! in_array($scheme, $allowHttpOnLocal ? ['http', 'https'] : ['https'], true)) {
            return null;
        }

        $allowedHosts = (array) config('services.ymsofterp.allowed_external_hosts', []);
        if ($allowedHosts === [] || ! in_array($host, $allowedHosts, true)) {
            return null;
        }

        return $raw;
    }

    private function enrichBannerMedia(?array $banner): ?array
    {
        if (! is_array($banner)) {
            return null;
        }

        $image = trim((string) ($banner['image'] ?? ''));
        if ($image === '') {
            return $banner;
        }

        $isVideo = ($banner['headIsVideo'] ?? false) === true
            || ($banner['headMediaType'] ?? '') === 'video'
            || preg_match('/\.(mp4|webm)(\?.*)?$/i', $image);

        if ($isVideo) {
            return $banner;
        }

        $banner['image_mobile'] = SiteMediaUrl::resize($image, 640);
        $banner['image_desktop'] = SiteMediaUrl::resize($image, 1600);

        return $banner;
    }

    private function shareHomeLcpShell(?array $banner): void
    {
        if (! is_array($banner) || empty($banner['image_mobile'])) {
            return;
        }

        $url = (string) $banner['image_mobile'];
        view()->share([
            'isSiteHome' => true,
            'lcpPreloadUrl' => $url,
            'lcpHeroUrl' => $url,
            'lcpTitle' => (string) ($banner['title'] ?? ''),
            'lcpSubtitle' => (string) ($banner['subtitle'] ?? ''),
        ]);
        request()->attributes->set('lcp_preload_url', $url);
    }
}

